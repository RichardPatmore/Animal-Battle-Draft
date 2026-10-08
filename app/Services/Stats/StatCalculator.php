<?php

namespace App\Services\Stats;

use App\Enums\MeasurementKey;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\AnimalStat;
use App\Services\Stats\Formulas\AgilityFormula;
use App\Services\Stats\Formulas\DefenceFormula;
use App\Services\Stats\Formulas\HealthFormula;
use App\Services\Stats\Formulas\IntelligenceFormula;
use App\Services\Stats\Formulas\SensesFormula;
use App\Services\Stats\Formulas\StatFormula;
use App\Services\Stats\Formulas\StealthFormula;
use App\Services\Stats\Formulas\StrengthFormula;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns real-world measurements into 0-100 game stats.
 *
 * 1. Each measurement is normalised to 0-1. Physical values use log10 min-max across the pool
 *    (so an ant and an elephant share a scale). 0-10 research scores are simply divided by 10.
 * 2. Each stat's formula takes a weighted average of whichever inputs are known (raw score).
 * 3. The raw score is blended 50/50 with its percentile rank in the pool so values spread out
 *    across 0-100 while keeping real gaps between animals.
 *
 * Percentiles depend on the whole pool, so stats are always recalculated together.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class StatCalculator
{
	private const RAW_WEIGHT = 0.5;

	/**
	 * @return list<StatFormula>
	 */
	public function formulas(): array
	{
		return [
			new StrengthFormula,
			new HealthFormula,
			new DefenceFormula,
			new SensesFormula,
			new IntelligenceFormula,
			new StealthFormula,
			new AgilityFormula,
		];
	}

	public function formulaFor(Stat $stat): StatFormula
	{
		foreach ($this->formulas() as $formula) {
			if ($formula->stat() === $stat) {
				return $formula;
			}
		}

		throw new \LogicException("No formula for stat {$stat->value}");
	}

	/**
	 * Recalculates derived stats for every animal and stores them, keeping manual overrides.
	 *
	 * @return int number of animals updated
	 */
	public function recalculateAll(): int
	{
		$animals = Animal::query()->with('measurements')->get();
		$results = $this->calculate($animals);

		DB::transaction(function () use ($results) {
			foreach ($results as $animalId => $stats) {
				foreach ($stats as $statValue => $result) {
					AnimalStat::query()->updateOrCreate(
						['animal_id' => $animalId, 'stat' => $statValue],
						['derived_value' => $result['value'], 'breakdown' => $result['breakdown']],
					);
				}
			}
		});

		return count($results);
	}

	/**
	 * Pure calculation over a pool of animals (measurements must be loaded).
	 *
	 * @param  Collection<int, Animal>  $animals
	 * @return array<int, array<string, array{value: int|null, breakdown: array<string, mixed>}>>
	 */
	public function calculate(Collection $animals): array
	{
		$bounds = $this->bounds($animals);
		$results = [];

		foreach ($this->formulas() as $formula) {
			$raws = [];
			$details = [];

			foreach ($animals as $animal) {
				[$raw, $inputs] = $this->rawScore($animal, $formula, $bounds);
				$raws[$animal->id] = $raw;
				$details[$animal->id] = $inputs;
			}

			$known = array_values(array_filter($raws, fn ($r) => $r !== null));
			sort($known);

			foreach ($animals as $animal) {
				$raw = $raws[$animal->id];
				$percentile = $raw === null ? null : self::percentileRank($raw, $known);
				$value = $raw === null || $percentile === null ? null : self::toStatValue($raw, $percentile);

				$results[$animal->id][$formula->stat()->value] = [
					'value' => $value,
					'breakdown' => [
						'explanation' => $formula->explanation(),
						'inputs' => $details[$animal->id],
						'raw' => $raw === null ? null : round($raw, 4),
						'percentile' => $percentile === null ? null : round($percentile, 4),
						'pool_size' => count($known),
						'missing_data' => $raw === null,
					],
				];
			}
		}

		return $results;
	}

	/**
	 * Re-scales already-computed stat blocks within a smaller pool (e.g. Bugs), so the strongest
	 * insect scores highly even though it is weak next to an elephant. Uses effective values,
	 * so manual overrides are respected.
	 *
	 * @param  array<int, array<string, int>>  $blocks  animal id => stat => value
	 * @return array<int, array<string, int>>
	 */
	public function rescaleWithinPool(array $blocks): array
	{
		if (count($blocks) < 2) {
			return $blocks;
		}

		$scaled = $blocks;

		foreach (Stat::cases() as $stat) {
			$values = array_map(fn (array $block) => $block[$stat->value], $blocks);
			$min = min($values);
			$max = max($values);
			$sorted = array_values($values);
			sort($sorted);

			foreach ($blocks as $id => $block) {
				$v = $block[$stat->value];
				$minMax = $max === $min ? 0.5 : ($v - $min) / ($max - $min);
				$scaled[$id][$stat->value] = self::toStatValue($minMax, self::percentileRank($v, $sorted));
			}
		}

		return $scaled;
	}

	/**
	 * @param  array<string, array{min: float, max: float}>  $bounds
	 * @return array{0: float|null, 1: list<array<string, mixed>>}
	 */
	private function rawScore(Animal $animal, StatFormula $formula, array $bounds): array
	{
		$weighted = 0.0;
		$totalWeight = 0.0;
		$inputs = [];

		foreach ($formula->inputs() as $input) {
			$value = $animal->measurementValue($input->key);

			if ($value === null) {
				$inputs[] = ['key' => $input->key->value, 'label' => $input->key->label(), 'value' => null, 'weight' => $input->weight];

				continue;
			}

			$normalised = $this->normalise($input->key, $value, $bounds);

			if ($input->invert) {
				$normalised = 1 - $normalised;
			}

			$weighted += $normalised * $input->weight;
			$totalWeight += $input->weight;

			$inputs[] = [
				'key' => $input->key->value,
				'label' => $input->key->label(),
				'value' => $value,
				'unit' => $input->key->unit(),
				'normalised' => round($normalised, 4),
				'weight' => round($input->weight, 4),
				'inverted' => $input->invert,
			];
		}

		return [$totalWeight > 0 ? $weighted / $totalWeight : null, $inputs];
	}

	/**
	 * @param  array<string, array{min: float, max: float}>  $bounds
	 */
	public function normalise(MeasurementKey $key, float $value, array $bounds): float
	{
		if ($key->isScore()) {
			return max(0.0, min(1.0, $value / 10));
		}

		$range = $bounds[$key->value] ?? null;

		if ($range === null) {
			return 0.5;
		}

		$scaled = $key->isLogScaled() ? log10(max($value, 1e-9)) : $value;

		if ($range['max'] - $range['min'] < 1e-9) {
			return 0.5;
		}

		return max(0.0, min(1.0, ($scaled - $range['min']) / ($range['max'] - $range['min'])));
	}

	/**
	 * Min/max of each physical measurement across the pool (in log10 space for log-scaled keys).
	 *
	 * @param  Collection<int, Animal>  $animals
	 * @return array<string, array{min: float, max: float}>
	 */
	public function bounds(Collection $animals): array
	{
		$bounds = [];

		foreach (MeasurementKey::cases() as $key) {
			if ($key->isScore()) {
				continue;
			}

			$values = $animals
				->map(fn (Animal $a) => $a->measurementValue($key))
				->filter(fn (?float $v) => $v !== null)
				->map(fn (float $v) => $key->isLogScaled() ? log10(max($v, 1e-9)) : $v);

			if ($values->isNotEmpty()) {
				$bounds[$key->value] = ['min' => (float) $values->min(), 'max' => (float) $values->max()];
			}
		}

		return $bounds;
	}

	/**
	 * Mid-rank percentile of a value within a sorted list, 0-1.
	 *
	 * @param  list<float|int>  $sorted
	 */
	public static function percentileRank(float $value, array $sorted): float
	{
		$n = count($sorted);

		if ($n <= 1) {
			return 0.5;
		}

		$below = 0;
		$equal = 0;

		foreach ($sorted as $v) {
			if ($v < $value) {
				$below++;
			} elseif ($v == $value) {
				$equal++;
			}
		}

		return ($below + 0.5 * $equal - 0.5) / ($n - 1);
	}

	public static function toStatValue(float $raw, float $percentile): int
	{
		$blended = self::RAW_WEIGHT * $raw + (1 - self::RAW_WEIGHT) * $percentile;

		return (int) max(0, min(100, round(100 * $blended)));
	}
}
