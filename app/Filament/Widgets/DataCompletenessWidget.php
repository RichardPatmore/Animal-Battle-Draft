<?php

namespace App\Filament\Widgets;

use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\AnimalMeasurement;
use App\Models\AnimalStat;
use App\Services\Animals\StarterDataImporter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard summary of how much research is left to do.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class DataCompletenessWidget extends StatsOverviewWidget
{
	protected function getStats(): array
	{
		$animals = Animal::query()->count();
		$published = Animal::query()->where('is_published', true)->count();
		$measurements = AnimalMeasurement::query()->count();
		$possible = max(1, $animals * count(MeasurementKey::cases()));
		$unverified = AnimalMeasurement::query()
			->where('is_manual', false)
			->whereHas('source', fn ($q) => $q->where('name', StarterDataImporter::SOURCE_NAME))
			->count();
		$overrides = AnimalStat::query()->whereNotNull('override_value')->count();

		return [
			Stat::make('Published animals', "{$published} / {$animals}"),
			Stat::make('Measurement coverage', round($measurements / $possible * 100).'%')
				->description("{$measurements} of {$possible} possible values"),
			Stat::make('Unverified starter estimates', (string) $unverified)
				->description('Lock a value once you have researched it')
				->color($unverified > 0 ? 'warning' : 'success'),
			Stat::make('Manual stat overrides', (string) $overrides),
		];
	}
}
