<?php

use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\AnimalMeasurement;
use App\Models\Category;
use App\Services\Categories\CategoryAssigner;

it('assigns animals by rule and keeps manual members', function () {
	$bee = Animal::factory()->create(['taxon_class' => 'Insecta']);
	$lion = Animal::factory()->create(['taxon_class' => 'Mammalia']);
	$category = Category::factory()->create([
		'rules' => [['field' => 'taxon_class', 'operator' => 'in', 'value' => ['Insecta', 'Arachnida']]],
	]);
	$category->animals()->attach($lion->id, ['is_manual' => true]);

	app(CategoryAssigner::class)->sync($category);

	expect($category->animals()->pluck('animals.id')->sort()->values()->all())->toBe([$bee->id, $lion->id]);
});

it('matches measurement rules', function () {
	$snake = Animal::factory()->create();
	$cow = Animal::factory()->create();
	AnimalMeasurement::query()->create(['animal_id' => $snake->id, 'key' => MeasurementKey::VenomScore, 'value_min' => 9]);
	AnimalMeasurement::query()->create(['animal_id' => $cow->id, 'key' => MeasurementKey::VenomScore, 'value_min' => 0]);

	$venomous = Category::factory()->create([
		'rules' => [['field' => 'measurement.venom_score', 'operator' => '>=', 'value' => 5]],
	]);

	app(CategoryAssigner::class)->sync($venomous);

	expect($venomous->animals()->pluck('animals.id')->all())->toBe([$snake->id]);
});
