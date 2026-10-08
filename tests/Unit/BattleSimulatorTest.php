<?php

use App\Enums\Stat;
use App\Services\Battle\BattleSimulator;

function statBlock(int $value): array
{
	return collect(Stat::cases())->mapWithKeys(fn (Stat $s) => [$s->value => $value])->all();
}

it('gives the stronger animal the better chance', function () {
	$result = (new BattleSimulator)->simulate(statBlock(85), statBlock(25), 'Hybrid', 'Mouse', 42);

	expect($result['win_chance'])->toBeGreaterThan(90.0)
		->and($result['edges'])->toHaveCount(6)
		->and(collect($result['edges'])->pluck('chance')->every(fn ($c) => $c > 50))->toBeTrue();
});

it('is close to a coin flip for identical animals', function () {
	$result = (new BattleSimulator)->simulate(statBlock(50), statBlock(50), 'A', 'B', 7);

	expect($result['win_chance'])->toBeGreaterThan(35.0)->toBeLessThan(65.0);
});

it('is deterministic for the same seed', function () {
	$sim = new BattleSimulator;
	$a = [...statBlock(60), 'agility' => 95, 'defence' => 20];
	$b = [...statBlock(55), 'strength' => 90];

	expect($sim->simulate($a, $b, 'A', 'B', 99))->toBe($sim->simulate($a, $b, 'A', 'B', 99));
});

it('narrates a fight that ends with the reported winner', function () {
	$result = (new BattleSimulator)->simulate(statBlock(70), statBlock(40), 'Hybrid', 'Rival', 3);

	expect($result['log'])->not->toBeEmpty()
		->and($result['rounds'])->toBeGreaterThan(0)
		->and($result['winner'])->toBeIn(['a', 'b'])
		->and(end($result['log'])['actor'])->toBe($result['winner'])
		->and(end($result['log'])['final'] ?? false)->toBeTrue();
});

it('maps stat gaps onto a logistic curve', function () {
	expect(BattleSimulator::logistic(0.0))->toBe(0.5)
		->and(BattleSimulator::logistic(40.0))->toBeGreaterThan(0.9)
		->and(BattleSimulator::logistic(-40.0))->toBeLessThan(0.1);
});
