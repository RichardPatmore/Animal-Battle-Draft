<?php

use App\Models\Animal;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;

beforeEach(function () {
	config(['services.admin.emails' => ['admin@example.com']]);
	$this->admin = User::factory()->create(['email' => 'admin@example.com']);
});

it('renders every admin page for an admin', function () {
	$animal = Animal::factory()->withStats()->create();
	$category = Category::factory()->create();
	$source = Source::query()->create(['name' => 'Test source']);

	$this->actingAs($this->admin);

	foreach ([
		'/admin',
		'/admin/animals',
		'/admin/animals/create',
		"/admin/animals/{$animal->slug}/edit",
		'/admin/categories',
		"/admin/categories/{$category->slug}/edit",
		'/admin/sources',
		"/admin/sources/{$source->id}/edit",
	] as $url) {
		$this->get($url)->assertOk();
	}
});

it('keeps non-admins out', function () {
	$this->actingAs(User::factory()->create(['email' => 'player@example.com']))
		->get('/admin/animals')
		->assertForbidden();
});
