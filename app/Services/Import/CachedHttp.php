<?php

namespace App\Services\Import;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * JSON HTTP client for importers that caches every raw response under storage/app/imports/{source}.
 *
 * Re-running an import is then free and offline, and the raw payloads are kept for auditing values.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CachedHttp
{
	private bool $fresh = false;

	/** Milliseconds to wait between live requests, to respect public API rate limits. */
	private int $throttleMs = 250;

	public function fresh(bool $fresh = true): static
	{
		$this->fresh = $fresh;

		return $this;
	}

	/**
	 * GET a JSON document. Returns null on 404 or when the request keeps failing.
	 *
	 * @param  array<string, mixed>  $query
	 * @param  array<string, string>  $headers
	 * @return array<string, mixed>|null
	 */
	public function getJson(string $source, string $url, array $query = [], array $headers = []): ?array
	{
		$path = $this->cachePath($source, $url, $query);

		if (! $this->fresh && File::exists($path)) {
			$cached = json_decode(File::get($path), true);

			return is_array($cached) ? $cached : null;
		}

		usleep($this->throttleMs * 1000);

		try {
			$response = Http::withHeaders([
				'User-Agent' => config('app.name').'/1.0 (educational animal game; '.config('app.url').')',
				'Accept' => 'application/json',
				...$headers,
			])
				->timeout(60)
				->retry(2, 1000, throw: false)
				->get($url, $query);
		} catch (ConnectionException) {
			return null;
		}

		if (! $response->successful()) {
			return null;
		}

		$data = $response->json();

		if (! is_array($data)) {
			return null;
		}

		File::ensureDirectoryExists(dirname($path));
		File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

		return $data;
	}

	/**
	 * @param  array<string, mixed>  $query
	 */
	private function cachePath(string $source, string $url, array $query): string
	{
		return storage_path('app/imports/'.$source.'/'.sha1($url.'?'.http_build_query($query)).'.json');
	}
}
