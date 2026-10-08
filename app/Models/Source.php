<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Citation for a measurement or fact (an API, paper, book or web page).
 *
 * @property int $id
 * @property string $name
 * @property string|null $url
 * @property Carbon|null $retrieved_at
 * @property string|null $notes
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['name', 'url', 'retrieved_at', 'notes'])]
class Source extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'retrieved_at' => 'datetime',
		];
	}

	/**
	 * Finds or creates the shared source row used by an importer and stamps the retrieval time.
	 */
	public static function forImporter(string $name, string $url): self
	{
		$source = self::query()->firstOrCreate(['name' => $name, 'url' => $url]);
		$source->update(['retrieved_at' => now()]);

		return $source;
	}

	/**
	 * @return HasMany<AnimalMeasurement, $this>
	 */
	public function measurements(): HasMany
	{
		return $this->hasMany(AnimalMeasurement::class);
	}

	/**
	 * @return HasMany<AnimalFact, $this>
	 */
	public function facts(): HasMany
	{
		return $this->hasMany(AnimalFact::class);
	}
}
