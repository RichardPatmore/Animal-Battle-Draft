<?php

use App\Enums\DraftStatus;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Draft;
use App\Services\Draft\DraftService;
use App\Services\Draft\HybridNamer;
use App\Services\Draft\Synergies;

function seedPool(int $count = 14): void
{
	Animal::factory()->count($count)->withStats()->create();
}

it('offers three distinct published animals', function () {
	seedPool();
	Animal::factory()->unpublished()->withStats()->create(['slug' => 'hidden']);

	$draft = app(DraftService::class)->start('session-a');

	expect($draft->current_offer)->toHaveCount(3)
		->and(array_unique($draft->current_offer))->toHaveCount(3)
		->and($draft->current_offer)->not->toContain(Animal::query()->where('slug', 'hidden')->value('id'));
});

it('produces the same offers for the same seed', function () {
	seedPool();
	$service = app(DraftService::class);

	$a = $service->start('session-a', seed: 42);
	$b = $service->start('session-b', seed: 42);

	expect($a->current_offer)->toBe($b->current_offer);
});

it('gives a lone first pick its full value in the chosen stat slot', function () {
	seedPool();
	$service = app(DraftService::class);
	$draft = $service->start('session-a');
	$animal = Animal::query()->with('stats')->findOrFail($draft->current_offer[0]);

	$service->pick($draft, $animal->id, Stat::Agility);

	expect($draft->fresh()->hybridStats()[Stat::Agility->value])->toBe($animal->statValue(Stat::Agility))
		->and($draft->fresh()->current_offer)->not->toContain($animal->id);
});

it('rejects animals that are not on offer and stats already filled', function () {
	seedPool();
	$service = app(DraftService::class);
	$draft = $service->start('session-a');
	$offered = $draft->current_offer;
	$notOffered = Animal::query()->whereNotIn('id', $offered)->value('id');

	expect(fn () => $service->pick($draft, $notOffered, Stat::Agility))->toThrow(InvalidArgumentException::class);

	$service->pick($draft, $offered[0], Stat::Agility);
	$draft->refresh();

	expect(fn () => $service->pick($draft, $draft->current_offer[0], Stat::Agility))->toThrow(InvalidArgumentException::class);
});

it('completes after every stat is filled and scores the hybrid', function () {
	seedPool();
	$service = app(DraftService::class);
	$draft = $service->start('session-a');

	foreach (Stat::cases() as $stat) {
		$draft->refresh();
		$service->pick($draft, $draft->current_offer[0], $stat);
	}

	$draft->refresh();
	$statTotal = array_sum($draft->hybridStats());
	$bonus = array_sum(array_column($draft->synergies ?? [], 'bonus'));

	expect($draft->status)->toBe(DraftStatus::Completed)
		->and($draft->picks)->toHaveCount(count(Stat::cases()))
		->and($draft->final_score)->toBe($statTotal + $bonus)
		->and($draft->hybrid_name)->toBe(Draft::DEFAULT_HYBRID_NAME)
		->and($draft->current_offer)->toBeNull();
});

it('refuses to start a category without enough animals', function () {
	seedPool(5);
	$category = Category::factory()->create();
	$category->animals()->attach(Animal::query()->pluck('id'));

	app(DraftService::class)->start('session-a', $category);
})->throws(InvalidArgumentException::class);

it('rescales stats within size-relative categories', function () {
	$bugs = Category::factory()->create(['size_relative' => true]);

	foreach (range(1, 11) as $i) {
		$bug = Animal::factory()->withStats([Stat::Strength->value => $i])->create();
		$bugs->animals()->attach($bug->id);
	}

	$blocks = app(DraftService::class)->statBlocks($bugs);
	$strengths = array_column($blocks, Stat::Strength->value);

	expect(max($strengths))->toBe(100)
		->and(min($strengths))->toBe(0);
});

it('blends each stat 80% from its own animal and 20% from the rest of the team', function () {
	seedPool();
	$service = app(DraftService::class);
	$draft = $service->start('session-a');

	$first = Animal::query()->with('stats')->findOrFail($draft->current_offer[0]);
	$service->pick($draft, $first->id, Stat::Agility);
	$draft->refresh();

	expect($draft->hybridStats()[Stat::Agility->value])->toBe($first->statValue(Stat::Agility));

	$second = Animal::query()->with('stats')->findOrFail($draft->current_offer[0]);
	$service->pick($draft, $second->id, Stat::Strength);
	$hybrid = $draft->fresh()->hybridStats();

	expect($draft->fresh()->picks->first()->animal_stats)->toBe($first->statBlock())
		->and($hybrid[Stat::Agility->value])->toBe((int) round(0.8 * $first->statValue(Stat::Agility) + 0.2 * $second->statValue(Stat::Agility)))
		->and($hybrid[Stat::Strength->value])->toBe((int) round(0.8 * $second->statValue(Stat::Strength) + 0.2 * $first->statValue(Stat::Strength)))
		->and($hybrid[Stat::Health->value])->toBeNull();
});

it('awards synergies for complementary stats', function () {
	$stats = array_fill_keys(array_map(fn (Stat $s) => $s->value, Stat::cases()), 40);
	$stats[Stat::Stealth->value] = 80;
	$stats[Stat::Strength->value] = 75;

	$keys = array_column(Synergies::evaluate($stats), 'key');

	expect($keys)->toBe(['ambush_predator']);
});

it('blends animal names into a hybrid name', function () {
	$namer = new HybridNamer;

	expect($namer->blend('cheetah', 'crocodile'))->toBe('cheetdile')
		->and($namer->name([
			['stat' => Stat::Agility, 'value' => 95, 'name' => 'Cheetah'],
			['stat' => Stat::Strength, 'value' => 90, 'name' => 'Saltwater crocodile'],
			['stat' => Stat::Senses, 'value' => 10, 'name' => 'Cat flea'],
		]))->toBe('Swift Cheetdile')
		->and($namer->name([
			['stat' => Stat::Health, 'value' => 96, 'name' => 'Whale shark'],
			['stat' => Stat::Senses, 'value' => 90, 'name' => 'Tiger shark'],
			['stat' => Stat::Strength, 'value' => 89, 'name' => 'Nile crocodile'],
		]))->toBe('Hardy Sharkdile');
});
