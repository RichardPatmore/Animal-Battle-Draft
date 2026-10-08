<?php

namespace App\Console\Commands\Animals;

use App\Models\Animal;
use App\Models\Source;

/**
 * Matches animals against the GBIF backbone taxonomy and derives the continents they live on
 * from occurrence records.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportGbif extends AnimalImportCommand
{
	private const API = 'https://api.gbif.org/v1';

	/**
	 * Continents holding at least this share of wild observations are listed as regions.
	 * High enough to ignore stray zoo and escapee records.
	 */
	private const REGION_THRESHOLD = 0.05;

	private const CONTINENTS = [
		'AFRICA' => 'Africa',
		'ANTARCTICA' => 'Antarctica',
		'ASIA' => 'Asia',
		'EUROPE' => 'Europe',
		'NORTH_AMERICA' => 'North America',
		'OCEANIA' => 'Oceania',
		'SOUTH_AMERICA' => 'South America',
	];

	protected $signature = 'animals:import-gbif
		'.self::COMMON_OPTIONS;

	protected $description = 'Import GBIF taxonomy keys and continent distribution';

	public function handle(): int
	{
		Source::forImporter('GBIF', 'https://www.gbif.org');
		$animals = $this->targets(fn ($q) => $q->whereNotNull('scientific_name'));
		$matched = 0;

		$this->withProgressBar($animals, function (Animal $animal) use (&$matched) {
			$match = $this->http()->getJson('gbif', self::API.'/species/match', [
				'name' => $animal->scientific_name,
				'strict' => 'true',
			]);

			if (! $match || ! isset($match['usageKey']) || ($match['matchType'] ?? 'NONE') === 'NONE') {
				return;
			}

			$matched++;
			$key = (int) ($match['acceptedUsageKey'] ?? $match['usageKey']);

			$this->fill($animal, 'gbif_key', $key);
			$this->fill($animal, 'taxon_class', $match['class'] ?? null);
			$this->fill($animal, 'taxon_order', $match['order'] ?? null);
			$this->fill($animal, 'taxon_family', $match['family'] ?? null);
			$this->fill($animal, 'regions', $this->continents($key));

			$animal->save();
		});

		$this->newLine(2);
		$this->components->info("Matched {$matched} of {$animals->count()} animals.");

		return self::SUCCESS;
	}

	/**
	 * @return list<string>
	 */
	private function continents(int $taxonKey): array
	{
		$data = $this->http()->getJson('gbif', self::API.'/occurrence/search', [
			'taxonKey' => $taxonKey,
			'basisOfRecord' => 'HUMAN_OBSERVATION',
			'occurrenceStatus' => 'PRESENT',
			'facet' => 'continent',
			'limit' => 0,
		]);

		$counts = $data['facets'][0]['counts'] ?? [];
		$total = array_sum(array_column($counts, 'count'));

		if ($total === 0) {
			return [];
		}

		$regions = [];
		foreach ($counts as $row) {
			if ($row['count'] / $total >= self::REGION_THRESHOLD && isset(self::CONTINENTS[$row['name']])) {
				$regions[] = self::CONTINENTS[$row['name']];
			}
		}

		sort($regions);

		return $regions;
	}
}
