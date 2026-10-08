<?php

use App\Enums\DraftStatus;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Draft;
use App\Services\Draft\DraftService;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
	$this->withoutVite();
	$this->withCookie(config('session.cookie'), Str::random(40));

	Animal::factory()->count(14)->withStats()->create();
	$this->category = Category::factory()->create(['slug' => 'everything']);
	$this->category->animals()->attach(Animal::query()->pluck('id'));
});

it('shows playable categories on the home page', function () {
	$this->get('/')
		->assertOk()
		->assertInertia(fn (Assert $page) => $page
			->component('home')
			->where('categories.0.slug', 'everything')
			->where('categories.0.playable', true)
			->has('featured'));
});

it('plays a full draft through to the result and a brawl', function () {
	$this->post('/drafts', ['category' => 'everything'])->assertRedirect();

	$draft = Draft::query()->latest('id')->firstOrFail();

	foreach (Stat::cases() as $stat) {
		$draft->refresh();

		$this->get("/drafts/{$draft->public_id}")
			->assertInertia(fn (Assert $page) => $page->component('draft/play')->has('offer', 3));

		$this->post("/drafts/{$draft->public_id}/picks", [
			'animal_id' => $draft->current_offer[0],
			'stat' => $stat->value,
		])->assertRedirect("/drafts/{$draft->public_id}");
	}

	expect($draft->fresh()->status)->toBe(DraftStatus::Completed);

	$this->get("/drafts/{$draft->public_id}")
		->assertInertia(fn (Assert $page) => $page
			->component('draft/result')
			->where('draft.is_owner', true)
			->where('draft.hybrid_name', Draft::DEFAULT_HYBRID_NAME)
			->where('draft.show_name_prompt', true)
			->has('name_suggestions')
			->has('dna', count(Stat::cases())));

	$this->patch("/drafts/{$draft->public_id}/name", ['hybrid_name' => 'Swift Cheetdile'])
		->assertRedirect();

	expect($draft->fresh()->hybrid_name)->toBe('Swift Cheetdile');

	$this->get("/drafts/{$draft->public_id}/brawl")
		->assertInertia(fn (Assert $page) => $page
			->component('brawl/index')
			->where('ranked_count', 14)
			->where('session', null)
			->has('draft.contributors', count(Stat::cases())));

	$this->post("/drafts/{$draft->public_id}/brawl/start")
		->assertRedirect("/drafts/{$draft->public_id}/brawl");

	$this->get("/drafts/{$draft->public_id}/brawl")
		->assertInertia(fn (Assert $page) => $page
			->where('session.active', true)
			->has('opponent'));

	$this->postJson("/drafts/{$draft->public_id}/brawl/fight")
		->assertOk()
		->assertJsonStructure(['fight' => ['hybrid_won', 'frames'], 'session']);
});

it('shows the gauntlet summary after a finished run with at least one win', function () {
	$this->post('/drafts', ['category' => 'everything']);
	$draft = Draft::query()->latest('id')->firstOrFail();

	foreach (Stat::cases() as $stat) {
		$draft->refresh();
		$this->post("/drafts/{$draft->public_id}/picks", [
			'animal_id' => $draft->current_offer[0],
			'stat' => $stat->value,
		]);
	}

	$key = 'gauntlet.'.$draft->public_id;

	$this->withSession([
		$key => [
			'level' => 2,
			'wins' => 1,
			'hybrid_hp' => 10.0,
			'max_hp' => 80.0,
			'active' => false,
			'history' => [
				['level' => 1, 'opponent_slug' => 'a', 'opponent_name' => 'Ant', 'won' => true],
			],
		],
	])->get("/drafts/{$draft->public_id}/brawl/summary")
		->assertOk()
		->assertInertia(fn (Assert $page) => $page->component('brawl/summary'));
});

it('does not let another session play someone else\'s draft', function () {
	$draft = app(DraftService::class)->start('someone-else');

	$this->get("/drafts/{$draft->public_id}")->assertRedirect('/');
	$this->post("/drafts/{$draft->public_id}/picks", [
		'animal_id' => $draft->current_offer[0],
		'stat' => Stat::Agility->value,
	])->assertForbidden();
});

it('shows the encyclopedia and animal profiles', function () {
	$animal = Animal::query()->firstOrFail();

	$this->get('/animals')->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/index')->has('animals', 14));
	$this->get("/animals/{$animal->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/show')->where('animal.slug', $animal->slug));
});

it('hides unpublished animals', function () {
	$hidden = Animal::factory()->unpublished()->create();

	$this->get("/animals/{$hidden->slug}")->assertNotFound();
});
