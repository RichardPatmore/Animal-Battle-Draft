<?php

namespace App\Console\Commands\Animals;

use App\Services\Animals\StarterDataImporter;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Illuminate\Console\Command;

/**
 * Loads (or refreshes) the curated starter CSVs in database/data.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportStarterData extends Command
{
	protected $signature = 'animals:import-starter
		{--path= : Directory containing animals.csv and facts.csv (defaults to database/data)}
		{--unpublished : Create new animals as unpublished}';

	protected $description = 'Import the curated starter animal list, measurements and facts from CSV';

	public function handle(StarterDataImporter $importer, StatCalculator $calculator, CategoryAssigner $assigner): int
	{
		$path = $this->option('path');
		$counts = $importer->import(is_string($path) ? base_path($path) : null, ! $this->option('unpublished'));

		$this->components->info("Animals: {$counts['animals']}, measurements written: {$counts['measurements']}, new facts: {$counts['facts']}");

		$calculator->recalculateAll();
		$assigner->syncAll();
		$this->components->info('Stats recalculated and categories synced.');

		return self::SUCCESS;
	}
}
