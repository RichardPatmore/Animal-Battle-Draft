<?php

namespace App\Console\Commands\Animals;

use App\Models\Animal;
use App\Models\Source;
use Illuminate\Support\Str;

/**
 * Pulls the English Wikipedia summary (description) and lead image, with attribution and licence
 * from Wikimedia Commons so images can be credited on animal cards.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ImportWikipedia extends AnimalImportCommand
{
	private const SUMMARY_URL = 'https://en.wikipedia.org/api/rest_v1/page/summary/';

	private const COMMONS_API = 'https://commons.wikimedia.org/w/api.php';

	protected $signature = 'animals:import-wikipedia
		'.self::COMMON_OPTIONS;

	protected $description = 'Import descriptions and credited images from Wikipedia / Wikimedia Commons';

	public function handle(): int
	{
		$source = Source::forImporter('Wikipedia', 'https://en.wikipedia.org');
		$animals = $this->targets();
		$updated = 0;

		$this->withProgressBar($animals, function (Animal $animal) use (&$updated) {
			$title = $animal->wikipedia_title ?: ($animal->scientific_name ?: $animal->common_name);
			$summary = $this->http()->getJson('wikipedia', self::SUMMARY_URL.rawurlencode(str_replace(' ', '_', $title)));

			if (! $summary || ($summary['type'] ?? null) === 'disambiguation') {
				return;
			}

			$canonical = $summary['titles']['canonical'] ?? null;
			$this->fill($animal, 'wikipedia_title', is_string($canonical) ? str_replace('_', ' ', $canonical) : null);
			$this->fill($animal, 'description', isset($summary['extract']) ? Str::limit((string) $summary['extract'], 1200) : null);

			$original = $summary['originalimage']['source'] ?? null;
			if (is_string($original) && ($this->option('force') || blank($animal->image_credit))) {
				$this->applyCommonsImage($animal, $original);
			}

			if ($animal->isDirty()) {
				$animal->save();
				$updated++;
			}
		});

		$source->touch();

		$this->newLine(2);
		$this->components->info("Updated {$updated} of {$animals->count()} animals.");

		return self::SUCCESS;
	}

	/**
	 * Looks up licence, author and a sized thumbnail for an upload.wikimedia.org image URL.
	 */
	private function applyCommonsImage(Animal $animal, string $imageUrl): void
	{
		$segments = explode('/', (string) parse_url($imageUrl, PHP_URL_PATH));
		$thumbIndex = array_search('thumb', $segments, true);
		$file = rawurldecode($thumbIndex !== false ? ($segments[$thumbIndex + 3] ?? end($segments)) : end($segments));

		$data = $this->http()->getJson('commons', self::COMMONS_API, [
			'action' => 'query',
			'titles' => 'File:'.$file,
			'prop' => 'imageinfo',
			'iiprop' => 'url|extmetadata',
			'iiurlwidth' => 800,
			'format' => 'json',
		]);

		$pages = $data['query']['pages'] ?? [];
		$info = is_array($pages) ? (array_values($pages)[0]['imageinfo'][0] ?? null) : null;

		if (! is_array($info)) {
			return;
		}

		$meta = $info['extmetadata'] ?? [];
		$artist = trim(strip_tags((string) ($meta['Artist']['value'] ?? '')));
		$licence = trim(strip_tags((string) ($meta['LicenseShortName']['value'] ?? '')));

		$animal->image_url = $info['thumburl'] ?? $info['url'] ?? $imageUrl;
		$animal->image_credit = $artist !== '' ? Str::limit($artist, 250) : 'Wikimedia Commons';
		$animal->image_licence = $licence !== '' ? $licence : null;
	}
}
