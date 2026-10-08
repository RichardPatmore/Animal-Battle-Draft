<?php

namespace App\Services\Animals;

use App\Enums\Confidence;
use App\Enums\ConservationStatus;
use App\Enums\Diet;
use App\Enums\Domain;
use App\Enums\FactType;
use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\AnimalFact;
use App\Models\AnimalMeasurement;
use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Loads the curated starter list from database/data/animals.csv and facts.csv.
 *
 * Measurement cells accept a single value ("80") or a range ("126~190"). Existing manual
 * measurements and profile fields already filled in by hand or by an importer are kept.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class StarterDataImporter
{
	public const SOURCE_NAME = 'Starter estimates (verify before relying on)';

	/**
	 * @return array{animals: int, measurements: int, facts: int}
	 */
	public function import(?string $directory = null, bool $publish = true): array
	{
		$directory ??= database_path('data');
		$source = Source::query()->firstOrCreate(
			['name' => self::SOURCE_NAME, 'url' => null],
			['notes' => 'Rough values compiled for the initial seed list. Replace with cited research.'],
		);

		$counts = ['animals' => 0, 'measurements' => 0, 'facts' => 0];

		DB::transaction(function () use ($directory, $source, $publish, &$counts) {
			foreach ($this->readCsv($directory.'/animals.csv') as $row) {
				$animal = $this->upsertAnimal($row, $publish);
				$counts['animals']++;
				$counts['measurements'] += $this->upsertMeasurements($animal, $row, $source);
			}

			$factsPath = $directory.'/facts.csv';
			if (File::exists($factsPath)) {
				$counts['facts'] = $this->importFacts($factsPath, $source);
			}
		});

		return $counts;
	}

	/**
	 * @param  array<string, string>  $row
	 */
	private function upsertAnimal(array $row, bool $publish): Animal
	{
		$animal = Animal::query()->firstOrNew(['slug' => $row['slug']]);

		$defaults = [
			'common_name' => $row['common_name'],
			'scientific_name' => $row['scientific_name'] ?: null,
			'taxon_class' => $row['taxon_class'] ?: null,
			'domain' => Domain::tryFrom($row['domain'] ?? ''),
			'diet' => Diet::tryFrom($row['diet'] ?? ''),
			'conservation_status' => ConservationStatus::tryFrom($row['status'] ?? ''),
		];

		foreach ($defaults as $field => $value) {
			if ($animal->getAttribute($field) === null && $value !== null) {
				$animal->setAttribute($field, $value);
			}
		}

		if (! $animal->exists) {
			$animal->is_apex_predator = ($row['apex'] ?? '0') === '1';
			$animal->is_published = $publish;
		}

		$animal->save();

		return $animal;
	}

	/**
	 * @param  array<string, string>  $row
	 */
	private function upsertMeasurements(Animal $animal, array $row, Source $source): int
	{
		$count = 0;

		foreach (MeasurementKey::cases() as $key) {
			$cell = trim($row[$key->value] ?? '');

			if ($cell === '') {
				continue;
			}

			[$min, $max] = self::parseRange($cell);

			$existing = AnimalMeasurement::query()
				->where('animal_id', $animal->id)
				->where('key', $key->value)
				->first();

			if ($existing && ($existing->is_manual || $existing->source_id !== $source->id)) {
				continue;
			}

			AnimalMeasurement::query()->updateOrCreate(
				['animal_id' => $animal->id, 'key' => $key->value],
				[
					'value_min' => $min,
					'value_max' => $max,
					'source_id' => $source->id,
					'confidence' => Confidence::Low,
					'is_manual' => false,
				],
			);
			$count++;
		}

		return $count;
	}

	private function importFacts(string $path, Source $source): int
	{
		$count = 0;
		$order = [];

		foreach ($this->readCsv($path) as $row) {
			$animal = Animal::query()->where('slug', $row['slug'])->first();
			$type = FactType::tryFrom($row['type']);

			if (! $animal || ! $type) {
				continue;
			}

			$order[$animal->id] = ($order[$animal->id] ?? 0) + 1;

			$fact = AnimalFact::query()->firstOrCreate(
				['animal_id' => $animal->id, 'body' => $row['body']],
				['type' => $type, 'source_id' => $source->id, 'sort_order' => $order[$animal->id]],
			);

			if ($fact->wasRecentlyCreated) {
				$count++;
			}

			if ($type === FactType::Habitat && $animal->habitat_summary === null) {
				$animal->update(['habitat_summary' => $row['body']]);
			}
		}

		return $count;
	}

	/**
	 * Parses "80" or "126~190" into [min, max|null].
	 *
	 * @return array{0: float, 1: float|null}
	 */
	public static function parseRange(string $cell): array
	{
		if (str_contains($cell, '~')) {
			[$min, $max] = array_map('floatval', explode('~', $cell, 2));

			return [min($min, $max), max($min, $max)];
		}

		return [(float) $cell, null];
	}

	/**
	 * @return iterable<int, array<string, string>>
	 */
	private function readCsv(string $path): iterable
	{
		if (! File::exists($path)) {
			throw new RuntimeException("Missing starter data file: {$path}");
		}

		$handle = fopen($path, 'r');

		if ($handle === false) {
			throw new RuntimeException("Unable to open {$path}");
		}

		$header = fgetcsv($handle, escape: '');

		if ($header === false) {
			fclose($handle);

			return;
		}

		$header = array_map(fn ($h) => trim((string) $h), $header);

		while (($line = fgetcsv($handle, escape: '')) !== false) {
			if ($line === [null]) {
				continue;
			}

			yield array_combine($header, array_map(fn ($v) => (string) $v, array_pad($line, count($header), '')));
		}

		fclose($handle);
	}
}
