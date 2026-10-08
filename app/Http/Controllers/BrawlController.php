<?php

namespace App\Http\Controllers;

use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Draft;
use App\Models\DraftPick;
use App\Services\Battle\BattleSimulator;
use App\Services\Battle\GauntletService;
use App\Support\AnimalPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gauntlet brawl: the hybrid fights progressively tougher animals until it is knocked out.
 *
 * Opponents are ranked by base stat total. HP carries over between rounds.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class BrawlController extends Controller
{
	public function __construct(
		private readonly GauntletService $gauntlet,
		private readonly BattleSimulator $simulator,
	) {}

	public function index(Request $request, Draft $draft): Response|RedirectResponse
	{
		if (! $draft->isComplete()) {
			return redirect()->route('drafts.show', $draft);
		}

		$draft->load('category');
		$session = $request->session()->get($this->gauntlet->sessionKey($draft));
		$hybridStats = array_map('intval', $draft->hybridStats());
		$ranked = $this->gauntlet->rankedAnimals();

		$opponent = null;

		if (is_array($session) && ($session['active'] ?? false)) {
			$animal = $this->gauntlet->opponentForLevel($ranked, (int) $session['level'], $draft->seed);
			$opponent = $this->opponentCard($animal, (int) $session['level'], $draft->seed);
		}

		return Inertia::render('brawl/index', [
			'draft' => $this->hybrid($draft),
			'stats' => Stat::options(),
			'ranked_count' => $ranked->count(),
			'session' => $session,
			'opponent' => $opponent,
			'hybrid_damage_factor' => BattleSimulator::GAUNTLET_HYBRID_DAMAGE,
		]);
	}

	public function summary(Request $request, Draft $draft): Response|RedirectResponse
	{
		if (! $draft->isComplete()) {
			return redirect()->route('drafts.show', $draft);
		}

		$draft->load('category');
		$session = $request->session()->get($this->gauntlet->sessionKey($draft));

		if (! is_array($session) || ($session['active'] ?? false) || ($session['wins'] ?? 0) < 1) {
			return redirect()->route('brawl.index', $draft);
		}

		$name = $draft->hybrid_name ?? Draft::DEFAULT_HYBRID_NAME;
		$wins = (int) $session['wins'];
		$draftUrl = url()->route('drafts.show', $draft);
		$shareText = implode("\n", array_filter([
			"{$name} won {$wins} gauntlet bout".($wins === 1 ? '' : 's').' in Animal Battle Draft!',
			$draft->final_score ? "Draft score: {$draft->final_score}" : null,
			$draftUrl,
		]));

		return Inertia::render('brawl/summary', [
			'draft' => $this->hybrid($draft),
			'gauntlet' => [
				'wins' => $wins,
				'peak_level' => (int) $session['level'],
				'hybrid_hp' => (float) $session['hybrid_hp'],
				'max_hp' => (float) $session['max_hp'],
				'history' => $session['history'] ?? [],
			],
			'share' => [
				'url' => $draftUrl,
				'text' => $shareText,
			],
		]);
	}

	public function start(Request $request, Draft $draft): RedirectResponse
	{
		if (! $draft->isComplete()) {
			abort(404);
		}

		$hybridStats = array_map('intval', $draft->hybridStats());
		$request->session()->put($this->gauntlet->sessionKey($draft), $this->gauntlet->freshSession($hybridStats));

		return redirect()->route('brawl.index', $draft);
	}

	public function fight(Request $request, Draft $draft): JsonResponse
	{
		if (! $draft->isComplete()) {
			abort(404);
		}

		$key = $this->gauntlet->sessionKey($draft);
		$session = $request->session()->get($key);

		if (! is_array($session) || ! ($session['active'] ?? false)) {
			return response()->json(['message' => 'No active gauntlet.'], 422);
		}

		$hybridStats = array_map('intval', $draft->hybridStats());
		$ranked = $this->gauntlet->rankedAnimals();
		$level = (int) $session['level'];
		$animal = $this->gauntlet->opponentForLevel($ranked, $level, $draft->seed);
		$opponentStats = $this->gauntlet->scaleOpponentStats($animal->statBlock(), $level);

		$result = $this->simulator->gauntletDuel(
			$hybridStats,
			$opponentStats,
			(float) $session['hybrid_hp'],
			(string) $draft->hybrid_name,
			$animal->common_name,
			$draft->seed + $level * 31_337 + $animal->id,
		);

		$session['hybrid_hp'] = $result['hybrid_hp'];
		$session['history'] ??= [];
		$session['history'][] = [
			'level' => $level,
			'opponent_slug' => $animal->slug,
			'opponent_name' => $animal->common_name,
			'won' => $result['hybrid_won'],
		];
		$session = $this->gauntlet->afterFight($session, $result['hybrid_won']);
		$request->session()->put($key, $session);

		$nextOpponent = null;

		if ($session['active'] ?? false) {
			$next = $this->gauntlet->opponentForLevel($ranked, (int) $session['level'], $draft->seed);
			$nextOpponent = $this->opponentCard($next, (int) $session['level'], $draft->seed);
		}

		return response()->json([
			'fight' => $result,
			'session' => $session,
			'opponent' => $this->opponentCard($animal, $level, $draft->seed),
			'next_opponent' => $nextOpponent,
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function hybrid(Draft $draft): array
	{
		$draft->loadMissing(['category', 'picks.animal']);

		return [
			'id' => $draft->public_id,
			'hybrid_name' => $draft->hybrid_name,
			'final_score' => $draft->final_score,
			'category' => $draft->category->name ?? 'All Animals',
			'hybrid' => $draft->hybridStats(),
			'contributors' => $draft->picks
				->map(fn (DraftPick $pick) => [
					'slug' => $pick->animal->slug,
					'name' => $pick->animal->common_name,
					'art' => AnimalPresenter::art($pick->animal),
				])
				->unique('slug')
				->values()
				->all(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function opponentCard(Animal $animal, int $level, int $seed): array
	{
		$stats = $this->gauntlet->scaleOpponentStats($animal->statBlock(), $level);

		$health = (int) ($stats['health'] ?? 0);

		return [
			...AnimalPresenter::card($animal, $stats, $seed + $level),
			'level' => $level,
			'rank_total' => array_sum($animal->statBlock()),
			'combat_max_hp' => (int) round(60 + 1.4 * $health),
		];
	}
}
