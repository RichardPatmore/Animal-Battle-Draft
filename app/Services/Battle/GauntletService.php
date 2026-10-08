<?php

namespace App\Services\Battle;

use App\Models\Animal;
use App\Models\Draft;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Endless brawl gauntlet: fight progressively tougher animals ranked by base stat total.
 *
 * The hybrid keeps leftover HP between rounds, so damage from earlier fights still matters.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class GauntletService
{
	/** Extra opponent stat scaling per gauntlet level (level 1 = no bonus). */
	public const OPPONENT_SCALE_PER_LEVEL = 0.05;

	/**
	 * Published animals sorted weakest to strongest by the sum of their base stats.
	 *
	 * @return Collection<int, Animal>
	 */
	public function rankedAnimals(): Collection
	{
		return Animal::query()
			->published()
			->with('stats')
			->get()
			->sortBy(fn (Animal $a) => array_sum($a->statBlock()))
			->values();
	}

	public function maxHybridHp(array $hybridStats): float
	{
		$health = (float) ($hybridStats['health'] ?? 0);

		return 50 + 1.2 * $health;
	}

	/**
	 * Picks a published opponent for the gauntlet level using a deterministic seed.
	 */
	public function opponentForLevel(Collection $ranked, int $level, int $seed): Animal
	{
		$count = $ranked->count();
		$percentile = min(92, 8 + ($level - 1) * 7);
		$index = (int) floor($count * $percentile / 100);
		$index = max(0, min($count - 1, $index));

		$window = max(1, (int) floor($count * 0.04));
		$start = max(0, $index - (int) floor($window / 2));
		$end = min($count - 1, $start + $window - 1);
		$start = max(0, $end - $window + 1);

		$randomizer = new Randomizer(new Mt19937($seed + $level * 9973));

		return $ranked[$randomizer->getInt($start, $end)];
	}

	/**
	 * @param  array<string, int>  $opponentStats
	 * @return array<string, int>
	 */
	public function scaleOpponentStats(array $opponentStats, int $level): array
	{
		$scale = 1 + ($level - 1) * self::OPPONENT_SCALE_PER_LEVEL;

		return array_map(
			fn (int $v) => (int) max(0, min(100, round($v * $scale))),
			$opponentStats,
		);
	}

	public function sessionKey(Draft $draft): string
	{
		return 'gauntlet.'.$draft->public_id;
	}

	/**
	 * @return array{level: int, wins: int, hybrid_hp: float, max_hp: float, active: bool, history: list<array{level: int, opponent_slug: string, opponent_name: string, won: bool}>}
	 */
	public function freshSession(array $hybridStats): array
	{
		$max = $this->maxHybridHp($hybridStats);

		return [
			'level' => 1,
			'wins' => 0,
			'hybrid_hp' => $max,
			'max_hp' => $max,
			'active' => true,
			'history' => [],
		];
	}

	/**
	 * @param  array{level: int, wins: int, hybrid_hp: float, max_hp: float, active: bool, history: list<array{level: int, opponent_slug: string, opponent_name: string, won: bool}>}  $session
	 * @return array{level: int, wins: int, hybrid_hp: float, max_hp: float, active: bool, history: list<array{level: int, opponent_slug: string, opponent_name: string, won: bool}>}
	 */
	public function afterFight(array $session, bool $hybridWon): array
	{
		if (! $hybridWon) {
			$session['active'] = false;
			$session['hybrid_hp'] = 0;

			return $session;
		}

		$session['wins']++;
		$session['level']++;

		if ($session['hybrid_hp'] <= 0) {
			$session['active'] = false;
		}

		return $session;
	}
}
