<?php

namespace App\Http\Controllers;

use App\Enums\Stat;
use App\Models\Category;
use App\Models\Draft;
use App\Models\DraftPick;
use App\Services\Draft\DraftService;
use App\Services\Draft\HybridBlender;
use App\Services\Draft\HybridNamer;
use App\Services\Draft\Synergies;
use App\Support\AnimalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Starts drafts, shows the current round, records picks and shows finished hybrids.
 *
 * In-progress drafts are only playable by the browser session that started them. Completed
 * drafts can be viewed by anyone with the link.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class DraftController extends Controller
{
	public function __construct(
		private readonly DraftService $drafts,
		private readonly HybridNamer $namer,
	) {}

	public function store(Request $request): RedirectResponse
	{
		$validated = $request->validate([
			'category' => ['nullable', 'string', Rule::exists('categories', 'slug')->where('is_active', true)],
		]);

		$category = isset($validated['category'])
			? Category::query()->where('slug', $validated['category'])->firstOrFail()
			: null;

		try {
			$draft = $this->drafts->start($request->session()->getId(), $category);
		} catch (InvalidArgumentException $e) {
			return redirect()->route('home')->with('error', $e->getMessage());
		}

		return redirect()->route('drafts.show', $draft);
	}

	public function show(Request $request, Draft $draft): Response|RedirectResponse
	{
		$draft->load(['category', 'picks.animal.facts', 'picks.animal.stats']);

		if ($draft->isComplete()) {
			return Inertia::render('draft/result', $this->resultProps($request, $draft));
		}

		if (! $this->owns($request, $draft)) {
			return redirect()->route('home')->with('error', 'That draft belongs to someone else.');
		}

		$blocks = $this->drafts->statBlocks($draft->category);
		$round = $draft->picks->count() + 1;

		return Inertia::render('draft/play', [
			'draft' => [
				'id' => $draft->public_id,
				'round' => $round,
				'total_rounds' => count(Stat::cases()),
				'category' => $draft->category->name ?? 'All Animals',
				'size_relative' => (bool) $draft->category?->size_relative,
				'hybrid' => $draft->hybridStats(),
				'open_stats' => array_map(fn (Stat $s) => $s->value, $draft->openStats()),
				'picks' => $this->picks($draft),
				'own_weight' => HybridBlender::OWN_WEIGHT,
			],
			'offer' => $this->drafts->offeredAnimals($draft)
				->map(fn ($animal) => AnimalPresenter::card($animal, $blocks[$animal->id] ?? null, $draft->seed + $round))
				->all(),
			'stats' => Stat::options(),
		]);
	}

	public function pick(Request $request, Draft $draft): RedirectResponse
	{
		if (! $this->owns($request, $draft)) {
			abort(403);
		}

		$validated = $request->validate([
			'animal_id' => ['required', 'integer'],
			'stat' => ['required', Rule::enum(Stat::class)],
		]);

		try {
			$this->drafts->pick($draft, (int) $validated['animal_id'], Stat::from($validated['stat']));
		} catch (InvalidArgumentException $e) {
			return back()->with('error', $e->getMessage());
		}

		return redirect()->route('drafts.show', $draft);
	}

	public function updateName(Request $request, Draft $draft): RedirectResponse
	{
		if (! $draft->isComplete()) {
			abort(404);
		}

		if (! $this->owns($request, $draft)) {
			abort(403);
		}

		$validated = $request->validate([
			'hybrid_name' => ['required', 'string', 'min:1', 'max:64'],
		]);

		$name = trim($validated['hybrid_name']);
		$draft->update(['hybrid_name' => $name !== '' ? $name : Draft::DEFAULT_HYBRID_NAME]);

		return back();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resultProps(Request $request, Draft $draft): array
	{
		return [
			'draft' => [
				'id' => $draft->public_id,
				'hybrid_name' => $draft->hybrid_name,
				'final_score' => $draft->final_score,
				'max_score' => count(Stat::cases()) * 100 + array_sum(array_column(Synergies::definitions(), 'bonus')),
				'category' => $draft->category->name ?? 'All Animals',
				'hybrid' => $draft->hybridStats(),
				'synergies' => $draft->synergies ?? [],
				'picks' => $this->picks($draft),
				'is_owner' => $this->owns($request, $draft),
				'completed_at' => $draft->completed_at?->toDayDateTimeString(),
				'default_name' => Draft::DEFAULT_HYBRID_NAME,
				'show_name_prompt' => $this->owns($request, $draft)
					&& in_array($draft->hybrid_name, [null, '', Draft::DEFAULT_HYBRID_NAME], true),
			],
			'name_suggestions' => $this->owns($request, $draft)
				? $this->namer->suggestions($this->namePickData($draft))
				: [],
			'dna' => $draft->picks->map(fn (DraftPick $p) => AnimalPresenter::card($p->animal, factSeed: $draft->seed))->all(),
			'stats' => Stat::options(),
			'allSynergies' => array_map(fn ($s) => array_diff_key($s, ['requires' => true]), Synergies::definitions()),
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function picks(Draft $draft): array
	{
		return $draft->picks->map(fn (DraftPick $p) => [
			'round' => $p->round,
			'stat' => $p->stat->value,
			'value' => $p->value,
			'stats' => $p->statBlock(),
			'animal' => [
				'slug' => $p->animal->slug,
				'name' => $p->animal->common_name,
				'image_url' => $p->animal->image_url,
				'art' => AnimalPresenter::art($p->animal),
			],
		])->values()->all();
	}

	private function owns(Request $request, Draft $draft): bool
	{
		return hash_equals($draft->session_id, $request->session()->getId());
	}

	/**
	 * @return array<int, array{stat: Stat, value: int, name: string}>
	 */
	private function namePickData(Draft $draft): array
	{
		return $draft->picks->map(fn (DraftPick $p) => [
			'stat' => $p->stat,
			'value' => $p->value,
			'name' => $p->animal->common_name,
		])->values()->all();
	}
}
