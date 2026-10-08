<?php

use App\Enums\Stat;
use App\Services\Draft\HybridBlender;

it('splits the remaining 20% equally between the other animals', function () {
	$hybrid = HybridBlender::blend([
		['stat' => Stat::Agility, 'stats' => ['agility' => 100, 'strength' => 0]],
		['stat' => Stat::Strength, 'stats' => ['agility' => 50, 'strength' => 90]],
		['stat' => Stat::Defence, 'stats' => ['agility' => 0, 'strength' => 30, 'defence' => 70]],
	]);

	expect($hybrid['agility'])->toBe(85)
		->and($hybrid['strength'])->toBe(75)
		->and($hybrid['defence'])->toBe(56)
		->and($hybrid['health'])->toBeNull();
});

it('treats stats missing from an older pick snapshot as zero', function () {
	$hybrid = HybridBlender::blend([
		['stat' => Stat::Agility, 'stats' => ['agility' => 60]],
		['stat' => Stat::Strength, 'stats' => ['strength' => 80]],
	]);

	expect($hybrid['agility'])->toBe(48)
		->and($hybrid['strength'])->toBe(64);
});

it('merges legacy speed and attack keys when normalizing blocks', function () {
	$block = Stat::normalizeBlock(['agility' => 40, 'speed' => 90, 'strength' => 50, 'attack' => 70]);

	expect($block['agility'])->toBe(90)
		->and($block['strength'])->toBe(70);
});
