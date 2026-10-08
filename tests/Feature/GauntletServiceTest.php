<?php

use App\Models\Animal;
use App\Services\Battle\GauntletService;

it('picks stronger opponents as the gauntlet level rises', function () {
	$weak = Animal::factory()->withStats(['strength' => 10, 'health' => 10, 'defence' => 10, 'senses' => 10, 'intelligence' => 10, 'stealth' => 10, 'agility' => 10])->create(['common_name' => 'Weakling', 'slug' => 'weakling-test']);
	$strong = Animal::factory()->withStats(['strength' => 90, 'health' => 90, 'defence' => 90, 'senses' => 90, 'intelligence' => 90, 'stealth' => 90, 'agility' => 90])->create(['common_name' => 'Titan', 'slug' => 'titan-test']);

	$service = new GauntletService;
	$ranked = $service->rankedAnimals();

	expect($ranked->first()->id)->toBe($weak->id)
		->and($ranked->last()->id)->toBe($strong->id);

	$early = $service->opponentForLevel($ranked, 1, 42);
	$late = $service->opponentForLevel($ranked, 10, 42);

	expect(array_sum($early->statBlock()))->toBeLessThan(array_sum($late->statBlock()));
});
