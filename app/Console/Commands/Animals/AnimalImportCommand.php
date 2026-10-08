<?php

namespace App\Console\Commands\Animals;

use App\Models\Animal;
use App\Services\Import\CachedHttp;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared options for animal importers.
 *
 * Subclasses must include {@see self::COMMON_OPTIONS} in their signature:
 *   --animal=* limit to these slugs, --fresh bypass the response cache, --force overwrite filled profile fields.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
abstract class AnimalImportCommand extends Command
{
	protected const COMMON_OPTIONS = '{--animal=* : Only import these animal slugs}
		{--fresh : Ignore cached API responses}
		{--force : Overwrite profile fields that are already filled}';

	protected function http(): CachedHttp
	{
		return app(CachedHttp::class)->fresh((bool) $this->option('fresh'));
	}

	/**
	 * Animals targeted by this run.
	 *
	 * @param  (callable(Builder<Animal>): mixed)|null  $scope
	 * @return Collection<int, Animal>
	 */
	protected function targets(?callable $scope = null): Collection
	{
		$query = Animal::query()->orderBy('slug');

		/** @var list<string> $slugs */
		$slugs = (array) $this->option('animal');
		if ($slugs !== []) {
			$query->whereIn('slug', $slugs);
		}

		if ($scope) {
			$scope($query);
		}

		return $query->get();
	}

	/**
	 * Sets a profile field when it is empty, or always when --force is passed.
	 */
	protected function fill(Animal $animal, string $field, mixed $value): void
	{
		if ($value === null || $value === '' || $value === []) {
			return;
		}

		if ($this->option('force') || blank($animal->getAttribute($field))) {
			$animal->setAttribute($field, $value);
		}
	}
}
