<?php

namespace App\Console\Commands\Animals;

use App\Models\Animal;
use App\Models\Source;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Downloads a licence-free silhouette for each animal from PhyloPic.
 *
 * Only CC0 and Public Domain Mark images are accepted (PhyloPic's BY, NC and SA filters are all
 * switched off), so the art needs no attribution, although the artist is still credited. When
 * PhyloPic has nothing for the exact species, the closest genus, family or order silhouette is used and
 * the depicted taxon is recorded so it can be reviewed. SVGs are stored in public/silhouettes so
 * the game can tint them with CSS masks.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportSilhouettes extends AnimalImportCommand
{
	private const API = 'https://api.phylopic.org';

	private const GBIF = 'https://api.gbif.org/v1';

	private const LICENCES = [
		'creativecommons.org/publicdomain/zero' => 'CC0 1.0',
		'creativecommons.org/publicdomain/mark' => 'Public Domain Mark',
	];

	protected $signature = 'animals:import-silhouettes
		'.self::COMMON_OPTIONS;

	protected $description = 'Download CC0 / public-domain silhouettes from PhyloPic';

	public function handle(): int
	{
		$build = $this->http()->getJson('phylopic', self::API.'/')['build'] ?? null;

		if (! is_int($build)) {
			$this->components->error('Could not reach the PhyloPic API.');

			return self::FAILURE;
		}

		Source::forImporter('PhyloPic', 'https://www.phylopic.org');
		File::ensureDirectoryExists(public_path('silhouettes'));

		$animals = $this->targets(fn ($q) => $q->whereNotNull('gbif_key'));
		$exact = 0;
		$related = 0;
		$missing = [];

		$this->withProgressBar($animals, function (Animal $animal) use ($build, &$exact, &$related, &$missing) {
			if ($animal->silhouette_path && ! $this->option('force')) {
				return;
			}

			$image = $this->findImage($animal, $build);

			if (! $image || ! $this->download($image['svg'], $animal)) {
				$missing[] = $animal->slug;

				return;
			}

			$isExact = strcasecmp($image['taxon'], (string) $animal->scientific_name) === 0;
			$isExact ? $exact++ : $related++;

			$animal->fill([
				'silhouette_path' => '/silhouettes/'.$animal->slug.'.svg',
				'silhouette_credit' => $image['credit'],
				'silhouette_licence' => $image['licence'],
				'silhouette_source_url' => $image['page'],
				'silhouette_taxon' => $isExact ? null : $image['taxon'],
			])->save();
		});

		$this->newLine(2);
		$this->components->info("Exact species: {$exact}. Related species: {$related}. Not found: ".count($missing).'.');

		if ($missing !== []) {
			$this->components->bulletList($missing);
		}

		return self::SUCCESS;
	}

	/**
	 * @return array{svg: string, credit: string|null, licence: string, page: string, taxon: string}|null
	 */
	private function findImage(Animal $animal, int $build): ?array
	{
		foreach ($this->lineage((int) $animal->gbif_key) as $gbifKey) {
			$node = $this->http()->getJson('phylopic', self::API.'/resolve/gbif.org/species', [
				'objectIDs' => $gbifKey,
				'build' => $build,
			]);
			$nodeId = $node['uuid'] ?? null;

			if (! is_string($nodeId)) {
				continue;
			}

			$images = $this->http()->getJson('phylopic', self::API.'/images', [
				'build' => $build,
				'filter_clade' => $nodeId,
				'filter_license_by' => 'false',
				'filter_license_nc' => 'false',
				'filter_license_sa' => 'false',
				'embed_items' => 'true',
				'page' => 0,
			]);

			foreach ($images['_embedded']['items'] ?? [] as $item) {
				$licence = $this->licenceName((string) data_get($item, '_links.license.href'));
				$svg = data_get($item, '_links.vectorFile.href');

				$taxon = (string) data_get($item, '_links.specificNode.title', '');

				if ($licence && is_string($svg) && $this->sameOrder($animal, $taxon)) {
					return [
						'svg' => $svg,
						'credit' => is_string($item['attribution'] ?? null) ? $item['attribution'] : null,
						'licence' => $licence,
						'page' => 'https://www.phylopic.org/images/'.$item['uuid'],
						'taxon' => $taxon,
					];
				}
			}
		}

		return null;
	}

	/**
	 * The species key followed by its genus, family and order keys, most specific first.
	 *
	 * @return list<int>
	 */
	private function lineage(int $gbifKey): array
	{
		$species = $this->http()->getJson('gbif', self::GBIF.'/species/'.$gbifKey) ?? [];
		$keys = [$gbifKey];

		foreach (['genusKey', 'familyKey', 'orderKey'] as $rank) {
			if (is_int($species[$rank] ?? null)) {
				$keys[] = $species[$rank];
			}
		}

		return array_values(array_unique($keys));
	}

	/**
	 * Guards against PhyloPic nodes mapped to homonyms in other groups (e.g. a fish for a scorpion).
	 * Fallbacks never go above order, so a substitute must share the animal's GBIF order.
	 */
	private function sameOrder(Animal $animal, string $taxon): bool
	{
		if ($taxon === '' || strcasecmp($taxon, (string) $animal->scientific_name) === 0) {
			return true;
		}

		$expected = $this->http()->getJson('gbif', self::GBIF.'/species/'.$animal->gbif_key)['orderKey'] ?? null;
		$actual = $this->http()->getJson('gbif', self::GBIF.'/species/match', ['name' => $taxon])['orderKey'] ?? null;

		return ! is_int($expected) || $expected === $actual;
	}

	private function licenceName(string $url): ?string
	{
		foreach (self::LICENCES as $needle => $name) {
			if (str_contains($url, $needle)) {
				return $name;
			}
		}

		return null;
	}

	private function download(string $url, Animal $animal): bool
	{
		try {
			$response = Http::withHeaders(['User-Agent' => config('app.name').'/1.0 (educational animal game)'])
				->timeout(60)
				->retry(2, 1000, throw: false)
				->get($url);
		} catch (ConnectionException) {
			return false;
		}

		if (! $response->successful() || ! str_contains($response->body(), '<svg')) {
			return false;
		}

		File::put(public_path('silhouettes/'.$animal->slug.'.svg'), $response->body());

		return true;
	}
}
