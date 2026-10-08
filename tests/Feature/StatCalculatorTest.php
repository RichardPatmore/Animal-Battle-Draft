<?php

use App\Enums\MeasurementKey;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\AnimalMeasurement;
use App\Models\AnimalStat;
use App\Services\Stats\StatCalculator;

function measure(Animal $animal, MeasurementKey $key, float $value, bool $manual = false): void
{
	AnimalMeasurement::query()->create([
		'animal_id' => $animal->id,
		'key' => $key,
		'value_min' => $value,
		'is_manual' => $manual,
	]);
}

it('ranks faster animals higher for agility on a log scale', function () {
	$snail = Animal::factory()->create();
	$horse = Animal::factory()->create();
	$falcon = Animal::factory()->create();

	measure($snail, MeasurementKey::TopSpeedKmh, 0.05);
	measure($horse, MeasurementKey::TopSpeedKmh, 70);
	measure($falcon, MeasurementKey::TopSpeedKmh, 320);

	app(StatCalculator::class)->recalculateAll();

	$agility = fn (Animal $a) => $a->fresh('stats')->statValue(Stat::Agility);

	expect($agility($snail))->toBe(0)
		->and($agility($falcon))->toBe(100)
		->and($agility($horse))->toBeGreaterThan(50)->toBeLessThan(100);
});

it('keeps manual overrides when recalculating', function () {
	$animal = Animal::factory()->create();
	measure($animal, MeasurementKey::TopSpeedKmh, 10);
	AnimalStat::query()->create(['animal_id' => $animal->id, 'stat' => Stat::Agility, 'override_value' => 77]);

	app(StatCalculator::class)->recalculateAll();

	$row = $animal->fresh('stats')->stats->firstWhere('stat', Stat::Agility);

	expect($row->override_value)->toBe(77)
		->and($row->derived_value)->not->toBeNull()
		->and($animal->fresh('stats')->statValue(Stat::Agility))->toBe(77);
});

it('records a breakdown and flags missing data', function () {
	$animal = Animal::factory()->create();
	measure($animal, MeasurementKey::VisionScore, 8);

	app(StatCalculator::class)->recalculateAll();

	$stats = $animal->fresh('stats')->stats->keyBy(fn ($s) => $s->stat->value);

	expect($stats[Stat::Senses->value]->breakdown['missing_data'])->toBeFalse()
		->and($stats[Stat::Senses->value]->breakdown['inputs'][0]['key'])->toBe('vision_score')
		->and($stats[Stat::Agility->value]->derived_value)->toBeNull()
		->and($stats[Stat::Agility->value]->breakdown['missing_data'])->toBeTrue();
});
