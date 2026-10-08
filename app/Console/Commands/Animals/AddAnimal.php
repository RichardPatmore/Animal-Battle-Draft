<?php

namespace App\Console\Commands\Animals;

use App\Models\Animal;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Adds a new (unpublished) animal by scientific name and runs every importer for it.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AddAnimal extends Command
{
	protected $signature = 'animals:add
		{scientific_name : Binomial name, e.g. "Panthera leo"}
		{--name= : Common name (defaults to the scientific name until Wikipedia fills it in)}';

	protected $description = 'Create an unpublished animal and enrich it from Wikidata, Wikipedia, GBIF and IUCN';

	public function handle(): int
	{
		$scientific = trim((string) $this->argument('scientific_name'));
		$name = (string) ($this->option('name') ?: $scientific);
		$slug = Str::slug($name);

		if (Animal::query()->where('slug', $slug)->orWhere('scientific_name', $scientific)->exists()) {
			$this->components->error("An animal called {$name} ({$scientific}) already exists.");

			return self::FAILURE;
		}

		Animal::query()->create([
			'slug' => $slug,
			'common_name' => $name,
			'scientific_name' => $scientific,
			'is_published' => false,
		]);

		foreach (['animals:import-wikidata', 'animals:import-wikipedia', 'animals:import-gbif'] as $command) {
			$this->call($command, ['--animal' => [$slug]]);
		}

		if (config('services.iucn.token')) {
			$this->call('animals:import-iucn', ['--animal' => [$slug]]);
		}

		$this->call('animals:recalculate-stats');

		$this->components->info("Added {$name}. Research the score measurements in /admin, then publish it.");

		return self::SUCCESS;
	}
}
