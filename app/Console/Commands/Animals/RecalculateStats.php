<?php

namespace App\Console\Commands\Animals;

use App\Enums\Stat;
use App\Models\Animal;
use App\Services\Stats\StatCalculator;
use Illuminate\Console\Command;

/**
 * Re-derives every animal's game stats from its measurements.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class RecalculateStats extends Command
{
	protected $signature = 'animals:recalculate-stats {--show= : Print the stat block for an animal slug afterwards}';

	protected $description = 'Recalculate derived 0-100 stats for all animals (manual overrides are kept)';

	public function handle(StatCalculator $calculator): int
	{
		$count = $calculator->recalculateAll();
		$this->components->info("Recalculated stats for {$count} animals.");

		$slug = $this->option('show');
		if (is_string($slug) && $slug !== '') {
			$animal = Animal::query()->where('slug', $slug)->with('stats')->first();

			if (! $animal) {
				$this->components->error("No animal with slug {$slug}");

				return self::FAILURE;
			}

			$this->table(
				['Stat', 'Derived', 'Override', 'Game value'],
				array_map(function (Stat $stat) use ($animal) {
					$row = $animal->stats->firstWhere('stat', $stat);

					return [$stat->label(), $row->derived_value ?? '-', $row->override_value ?? '-', $animal->statValue($stat)];
				}, Stat::cases()),
			);
		}

		return self::SUCCESS;
	}
}
