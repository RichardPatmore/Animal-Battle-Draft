<?php

namespace Database\Seeders;

use App\Services\Animals\StarterDataImporter;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Illuminate\Database\Seeder;

/**
 * Seeds the curated starter animals, derives their stats and syncs category membership.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalSeeder extends Seeder
{
	public function run(StarterDataImporter $importer, StatCalculator $calculator, CategoryAssigner $assigner): void
	{
		$counts = $importer->import();
		$this->command->info("Imported {$counts['animals']} animals, {$counts['measurements']} measurements, {$counts['facts']} facts.");

		$calculator->recalculateAll();
		$assigner->syncAll();
	}
}
