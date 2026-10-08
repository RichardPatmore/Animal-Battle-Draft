<?php

namespace App\Console\Commands\Animals;

use App\Enums\ConservationStatus;
use App\Models\Animal;
use App\Models\Source;

/**
 * Imports Red List category, population trend and habitats from the IUCN Red List API v4.
 *
 * Requires a free token in IUCN_API_TOKEN (https://api.iucnredlist.org/users/sign_up).
 * IUCN is the authority on conservation status, so it overwrites the status even without --force.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportIucn extends AnimalImportCommand
{
	private const API = 'https://api.iucnredlist.org/api/v4';

	protected $signature = 'animals:import-iucn
		'.self::COMMON_OPTIONS;

	protected $description = 'Import IUCN Red List status, population trend and habitats';

	public function handle(): int
	{
		$token = config('services.iucn.token');

		if (! is_string($token) || $token === '') {
			$this->components->error('Set IUCN_API_TOKEN in .env first. Get a free token at https://api.iucnredlist.org/users/sign_up');

			return self::FAILURE;
		}

		Source::forImporter('IUCN Red List', 'https://www.iucnredlist.org');
		$headers = ['Authorization' => $token];
		$animals = $this->targets(fn ($q) => $q->whereNotNull('scientific_name'));
		$updated = 0;

		$this->withProgressBar($animals, function (Animal $animal) use ($headers, &$updated) {
			$parts = explode(' ', (string) $animal->scientific_name);

			if (count($parts) < 2) {
				return;
			}

			$taxon = $this->http()->getJson('iucn', self::API.'/taxa/scientific_name', [
				'genus_name' => $parts[0],
				'species_name' => $parts[1],
			], $headers);

			$assessments = collect(self::listOf($taxon['assessments'] ?? null));
			$latest = $assessments->firstWhere('latest', true) ?? $assessments->sortByDesc('year_published')->first();

			if (! is_array($latest)) {
				return;
			}

			$code = $latest['red_list_category_code'] ?? null;
			$status = is_string($code) ? ConservationStatus::tryFrom($code) : null;

			if ($status) {
				$animal->conservation_status = $status;
			}

			if (isset($latest['assessment_id'])) {
				$this->applyAssessment($animal, (int) $latest['assessment_id'], $headers);
			}

			if ($animal->isDirty()) {
				$animal->save();
				$updated++;
			}
		});

		$this->newLine(2);
		$this->components->info("Updated {$updated} of {$animals->count()} animals.");

		return self::SUCCESS;
	}

	/**
	 * @param  array<string, string>  $headers
	 */
	private function applyAssessment(Animal $animal, int $assessmentId, array $headers): void
	{
		$assessment = $this->http()->getJson('iucn', self::API.'/assessment/'.$assessmentId, [], $headers);

		if (! $assessment) {
			return;
		}

		$trend = data_get($assessment, 'population_trend.description.en');
		if (is_string($trend)) {
			$animal->population_trend = $trend;
		}

		$habitats = collect(self::listOf($assessment['habitats'] ?? null))
			->map(fn ($h) => data_get($h, 'description.en'))
			->filter(fn ($h) => is_string($h) && $h !== '')
			->unique()
			->values()
			->all();

		$this->fill($animal, 'habitats', $habitats);
	}

	/**
	 * The API returns a JSON array here, or nothing when the taxon is unknown.
	 *
	 * @return array<int|string, mixed>
	 */
	private static function listOf(mixed $value): array
	{
		return is_array($value) ? $value : [];
	}
}
