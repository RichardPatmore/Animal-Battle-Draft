<?php

namespace App\Services\Categories;

use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * Applies category rules to animals and syncs rule-based pivot rows, leaving manual attachments alone.
 *
 * A rule is {field, operator, value}. Fields are animal columns (e.g. taxon_class, domain,
 * is_apex_predator) or "measurement.<key>" for a researched measurement (e.g. measurement.venom_score).
 * Operators: =, !=, >, >=, <, <=, in, not_in.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CategoryAssigner
{
	public const OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'in', 'not_in'];

	/**
	 * Sync every active rule-based category. Returns [category slug => member count].
	 *
	 * @return array<string, int>
	 */
	public function syncAll(): array
	{
		$animals = Animal::query()->with('measurements')->get();
		$counts = [];

		foreach (Category::query()->get() as $category) {
			$counts[$category->slug] = $this->sync($category, $animals);
		}

		return $counts;
	}

	/**
	 * Re-evaluate one category's rules. Categories without rules are manual-only and left untouched.
	 *
	 * @param  Collection<int, Animal>|null  $animals  preloaded animals with measurements
	 * @return int total member count after syncing
	 */
	public function sync(Category $category, ?Collection $animals = null): int
	{
		$rules = $category->rules ?? [];

		if ($rules === []) {
			return $category->animals()->count();
		}

		$animals ??= Animal::query()->with('measurements')->get();

		$matchingIds = $animals
			->filter(fn (Animal $animal) => $this->matches($animal, $rules))
			->pluck('id')
			->all();

		$manualIds = $category->animals()->wherePivot('is_manual', true)->pluck('animals.id')->all();

		$category->animals()->wherePivot('is_manual', false)->detach();

		$attach = [];
		foreach (array_diff($matchingIds, $manualIds) as $id) {
			$attach[$id] = ['is_manual' => false];
		}
		$category->animals()->attach($attach);

		return count($attach) + count($manualIds);
	}

	/**
	 * @param  list<array{field: string, operator: string, value: mixed}>  $rules
	 */
	public function matches(Animal $animal, array $rules): bool
	{
		foreach ($rules as $rule) {
			if (! $this->matchesRule($animal, $rule)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param  array{field: string, operator: string, value: mixed}  $rule
	 */
	private function matchesRule(Animal $animal, array $rule): bool
	{
		$actual = $this->resolveField($animal, $rule['field']);
		$expected = $rule['value'];

		if ($actual === null) {
			return $rule['operator'] === '!=' || $rule['operator'] === 'not_in';
		}

		return match ($rule['operator']) {
			'=' => $this->looseEquals($actual, $expected),
			'!=' => ! $this->looseEquals($actual, $expected),
			'>' => (float) $actual > (float) $expected,
			'>=' => (float) $actual >= (float) $expected,
			'<' => (float) $actual < (float) $expected,
			'<=' => (float) $actual <= (float) $expected,
			'in' => $this->inList($actual, $expected),
			'not_in' => ! $this->inList($actual, $expected),
			default => false,
		};
	}

	private function resolveField(Animal $animal, string $field): mixed
	{
		if (str_starts_with($field, 'measurement.')) {
			$key = MeasurementKey::tryFrom(substr($field, strlen('measurement.')));

			return $key ? $animal->measurementValue($key) : null;
		}

		return $animal->getAttributes()[$field] ?? null;
	}

	private function looseEquals(mixed $actual, mixed $expected): bool
	{
		if (is_string($expected) && in_array(strtolower(trim($expected)), ['true', 'false'], true)) {
			$expected = strtolower(trim($expected)) === 'true';
		}

		if (is_bool($expected)) {
			return (bool) $actual === $expected;
		}

		return strtolower((string) $actual) === strtolower((string) $expected);
	}

	private function inList(mixed $actual, mixed $expected): bool
	{
		$list = is_array($expected) ? $expected : array_map('trim', explode(',', (string) $expected));

		foreach ($list as $item) {
			if ($this->looseEquals($actual, $item)) {
				return true;
			}
		}

		return false;
	}
}
