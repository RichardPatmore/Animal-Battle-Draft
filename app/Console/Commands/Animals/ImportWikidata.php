<?php

namespace App\Console\Commands\Animals;

use App\Enums\Confidence;
use App\Enums\ConservationStatus;
use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\Source;
use App\Services\Import\MeasurementWriter;
use Illuminate\Support\Collection;

/**
 * Enriches animals from Wikidata via SPARQL.
 *
 * Resolves missing Q-IDs from the scientific name (P225), then pulls conservation status (P141),
 * English Wikipedia title, image (P18) and SI-normalised quantities:
 * mass (P2067), length (P2043) and lifespan (P2250). Multiple values (e.g. male and female mass)
 * become a min-max range.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportWikidata extends AnimalImportCommand
{
	private const ENDPOINT = 'https://query.wikidata.org/sparql';

	private const SECONDS_PER_YEAR = 31_557_600;

	/**
	 * Qualifiers that don't change what a quantity describes: sex (P21), determination method (P459),
	 * sourcing circumstances such as "approximately" (P1480) and object has role (P3831), which is
	 * only accepted when the role is an adult one (e.g. "adult weight" rather than "birth weight").
	 * Statements with any other qualifier are ignored.
	 */
	private const ALLOWED_QUALIFIERS = ['P21', 'P459', 'P1480', 'P3831'];

	/** Reject imported values more than this many times larger or smaller than the current value. */
	private const SANITY_FACTOR = 5;

	protected $signature = 'animals:import-wikidata
		{--batch=25 : Animals per SPARQL query}
		'.self::COMMON_OPTIONS;

	protected $description = 'Import taxonomy, conservation status, images and body measurements from Wikidata';

	public function handle(MeasurementWriter $writer): int
	{
		$animals = $this->targets();
		$source = Source::forImporter('Wikidata', 'https://www.wikidata.org');
		$batchSize = max(1, (int) $this->option('batch'));

		$this->components->info('Resolving Wikidata IDs from scientific names…');
		$this->resolveIds($animals->filter(fn (Animal $a) => ! $a->wikidata_id && $a->scientific_name)->values()->all(), $batchSize);

		$withIds = $animals->filter(fn (Animal $a) => (bool) $a->wikidata_id)->values();
		$this->components->info("Fetching profiles and measurements for {$withIds->count()} animals…");

		$written = 0;
		$skipped = 0;

		$this->withProgressBar($withIds->chunk($batchSize), function (Collection $chunk) use ($source, $writer, &$written, &$skipped) {
			/** @var array<string, Animal> $byQid */
			$byQid = $chunk->keyBy('wikidata_id')->all();
			$this->importProfiles($byQid);

			foreach ($this->fetchQuantities(array_keys($byQid)) as $qid => $quantities) {
				$animal = $byQid[$qid] ?? null;
				if (! $animal) {
					continue;
				}

				foreach ($quantities as $keyValue => $values) {
					if ($values === []) {
						continue;
					}

					$key = MeasurementKey::from($keyValue);
					$min = min($values);
					$max = max($values);

					if (! $this->isPlausible($animal, $key, ($min + $max) / 2)) {
						$this->newLine();
						$this->components->warn("Skipped {$animal->slug} {$key->value} {$min}-{$max}: too far from the current value, review manually.");
						$skipped++;

						continue;
					}

					$ok = $writer->write($animal, $key, $min, $max, $source, Confidence::Medium);
					$ok ? $written++ : $skipped++;
				}
			}
		});

		$this->newLine(2);
		$this->components->info("Measurements written: {$written}. Skipped (manual values kept): {$skipped}.");
		$this->components->warn('Run animals:recalculate-stats to update game stats.');

		return self::SUCCESS;
	}

	/**
	 * Guards against Wikidata values that describe something else (eggs, records, unit mistakes).
	 */
	private function isPlausible(Animal $animal, MeasurementKey $key, float $value): bool
	{
		$current = $animal->measurementValue($key);

		if ($current === null || $current <= 0 || $this->option('force')) {
			return true;
		}

		$ratio = $value / $current;

		return $ratio <= self::SANITY_FACTOR && $ratio >= 1 / self::SANITY_FACTOR;
	}

	/**
	 * @param  array<int, Animal>  $animals
	 * @param  positive-int  $batchSize
	 */
	private function resolveIds(array $animals, int $batchSize): void
	{
		foreach (array_chunk($animals, $batchSize) as $chunk) {
			$names = implode(' ', array_map(fn (Animal $a) => '"'.addslashes((string) $a->scientific_name).'"', $chunk));

			$rows = $this->sparql(<<<SPARQL
				SELECT ?item ?name ?links WHERE {
					VALUES ?name { {$names} }
					?item wdt:P225 ?name ;
						wikibase:sitelinks ?links .
				}
				SPARQL);

			$best = [];
			foreach ($rows as $row) {
				$name = $row['name']['value'];
				$links = (int) $row['links']['value'];
				if (! isset($best[$name]) || $links > $best[$name]['links']) {
					$best[$name] = ['qid' => $this->qid($row['item']['value']), 'links' => $links];
				}
			}

			foreach ($chunk as $animal) {
				$match = $best[$animal->scientific_name] ?? null;

				if (! $match) {
					$this->components->warn("No Wikidata match for {$animal->common_name} ({$animal->scientific_name})");

					continue;
				}

				if (Animal::query()->where('wikidata_id', $match['qid'])->whereKeyNot($animal->id)->exists()) {
					continue;
				}

				$animal->update(['wikidata_id' => $match['qid']]);
			}
		}
	}

	/**
	 * @param  array<string, Animal>  $byQid
	 */
	private function importProfiles(array $byQid): void
	{
		$values = implode(' ', array_map(fn ($q) => "wd:{$q}", array_keys($byQid)));

		$rows = $this->sparql(<<<SPARQL
			SELECT ?item ?status ?image ?article WHERE {
				VALUES ?item { {$values} }
				OPTIONAL { ?item wdt:P141 ?status . }
				OPTIONAL { ?item wdt:P18 ?image . }
				OPTIONAL {
					?article schema:about ?item ;
						schema:isPartOf <https://en.wikipedia.org/> .
				}
			}
			SPARQL);

		$profiles = [];
		foreach ($rows as $row) {
			$qid = $this->qid($row['item']['value']);
			foreach (['status', 'image', 'article'] as $field) {
				if (isset($row[$field]) && ! isset($profiles[$qid][$field])) {
					$profiles[$qid][$field] = $row[$field]['value'];
				}
			}
		}

		foreach ($profiles as $qid => $profile) {
			$animal = $byQid[$qid] ?? null;
			if (! $animal) {
				continue;
			}

			$status = isset($profile['status']) ? ConservationStatus::fromWikidataId($this->qid($profile['status'])) : null;
			$this->fill($animal, 'conservation_status', $status);

			if (isset($profile['article'])) {
				$this->fill($animal, 'wikipedia_title', rawurldecode(basename($profile['article'])));
			}

			if (isset($profile['image'])) {
				$this->fill($animal, 'image_url', $this->commonsThumb($profile['image']));
			}

			$animal->save();
		}
	}

	/**
	 * @param  list<string>  $qids
	 * @return array<string, array<string, list<float>>> qid => measurement key => values
	 */
	private function fetchQuantities(array $qids): array
	{
		$values = implode(' ', array_map(fn ($q) => "wd:{$q}", $qids));

		$rows = $this->sparql(<<<SPARQL
			SELECT ?item ?prop ?amount
				(GROUP_CONCAT(DISTINCT ?qualifier; separator=",") AS ?qualifiers)
				(GROUP_CONCAT(DISTINCT ?roleLabel; separator=",") AS ?roles)
			WHERE {
				VALUES ?item { {$values} }
				VALUES (?prop ?p ?psn) {
					("mass" p:P2067 psn:P2067)
					("length" p:P2043 psn:P2043)
					("lifespan" p:P2250 psn:P2250)
				}
				?item ?p ?st .
				?st ?psn ?norm ;
					wikibase:rank ?rank .
				FILTER(?rank != wikibase:DeprecatedRank)
				?norm wikibase:quantityAmount ?amount .
				OPTIONAL {
					?st ?pq ?qv .
					?qualifierProp wikibase:qualifier ?pq .
					BIND(STRAFTER(STR(?qualifierProp), "entity/") AS ?qualifier)
				}
				OPTIONAL {
					?st pq:P3831 ?role .
					?role rdfs:label ?roleLabel .
					FILTER(LANG(?roleLabel) = "en")
				}
			}
			GROUP BY ?item ?prop ?amount ?st
			SPARQL);

		$map = ['mass' => MeasurementKey::MassKg, 'length' => MeasurementKey::LengthM, 'lifespan' => MeasurementKey::LifespanYears];
		$result = [];

		foreach ($rows as $row) {
			$key = $map[$row['prop']['value']] ?? null;
			$amount = (float) $row['amount']['value'];
			$qualifiers = array_filter(explode(',', $row['qualifiers']['value'] ?? ''));
			$roles = array_filter(explode(',', $row['roles']['value'] ?? ''));
			$nonAdultRole = array_filter($roles, fn (string $role) => ! str_contains(strtolower($role), 'adult')) !== [];

			if (! $key || $amount <= 0 || $nonAdultRole || array_diff($qualifiers, self::ALLOWED_QUALIFIERS) !== []) {
				continue;
			}

			if ($key === MeasurementKey::LifespanYears) {
				$amount /= self::SECONDS_PER_YEAR;
			}

			$result[$this->qid($row['item']['value'])][$key->value][] = $amount;
		}

		return $result;
	}

	/**
	 * @return list<array<string, array{value: string}>>
	 */
	private function sparql(string $query): array
	{
		$data = $this->http()->getJson('wikidata', self::ENDPOINT, ['query' => $query, 'format' => 'json']);

		/** @var list<array<string, array{value: string}>> $bindings */
		$bindings = $data['results']['bindings'] ?? [];

		return $bindings;
	}

	private function qid(string $uri): string
	{
		return basename($uri);
	}

	/**
	 * Converts a Commons FilePath URI into an 800px thumbnail URL.
	 */
	private function commonsThumb(string $filePathUri): string
	{
		return $filePathUri.(str_contains($filePathUri, '?') ? '&' : '?').'width=800';
	}
}
