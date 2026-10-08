<?php

namespace App\Models;

use App\Enums\ConservationStatus;
use App\Enums\Diet;
use App\Enums\Domain;
use App\Enums\MeasurementKey;
use App\Enums\Stat;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A real-world species with researched measurements, derived game stats and educational facts.
 *
 * @property int $id
 * @property string $slug
 * @property string $common_name
 * @property string|null $scientific_name
 * @property string|null $wikidata_id
 * @property string|null $wikipedia_title
 * @property int|null $gbif_key
 * @property string|null $taxon_class
 * @property string|null $taxon_order
 * @property string|null $taxon_family
 * @property Domain|null $domain
 * @property Diet|null $diet
 * @property bool $is_apex_predator
 * @property ConservationStatus|null $conservation_status
 * @property string|null $population_trend
 * @property string|null $habitat_summary
 * @property list<string>|null $habitats
 * @property list<string>|null $regions
 * @property string|null $description
 * @property string|null $image_url
 * @property string|null $image_credit
 * @property string|null $image_licence
 * @property string|null $silhouette_path public path to a locally stored CC0 / public-domain silhouette
 * @property string|null $silhouette_credit
 * @property string|null $silhouette_licence
 * @property string|null $silhouette_source_url
 * @property string|null $silhouette_taxon set when the silhouette is of a related species
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, AnimalMeasurement> $measurements
 * @property-read Collection<int, AnimalStat> $stats
 * @property-read Collection<int, AnimalFact> $facts
 * @property-read Collection<int, Category> $categories
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable([
	'slug', 'common_name', 'scientific_name', 'wikidata_id', 'wikipedia_title', 'gbif_key',
	'taxon_class', 'taxon_order', 'taxon_family', 'domain', 'diet', 'is_apex_predator',
	'conservation_status', 'population_trend', 'habitat_summary', 'habitats', 'regions',
	'description', 'image_url', 'image_credit', 'image_licence', 'silhouette_path', 'silhouette_credit',
	'silhouette_licence', 'silhouette_source_url', 'silhouette_taxon', 'is_published',
])]
class Animal extends Model
{
	/** @use HasFactory<AnimalFactory> */
	use HasFactory;

	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'domain' => Domain::class,
			'diet' => Diet::class,
			'conservation_status' => ConservationStatus::class,
			'is_apex_predator' => 'boolean',
			'is_published' => 'boolean',
			'habitats' => 'array',
			'regions' => 'array',
		];
	}

	public function getRouteKeyName(): string
	{
		return 'slug';
	}

	/**
	 * @return HasMany<AnimalMeasurement, $this>
	 */
	public function measurements(): HasMany
	{
		return $this->hasMany(AnimalMeasurement::class);
	}

	/**
	 * @return HasMany<AnimalStat, $this>
	 */
	public function stats(): HasMany
	{
		return $this->hasMany(AnimalStat::class);
	}

	/**
	 * @return HasMany<AnimalFact, $this>
	 */
	public function facts(): HasMany
	{
		return $this->hasMany(AnimalFact::class)->orderBy('sort_order');
	}

	/**
	 * @return BelongsToMany<Category, $this>
	 */
	public function categories(): BelongsToMany
	{
		return $this->belongsToMany(Category::class)->withPivot('is_manual');
	}

	/**
	 * @param  Builder<Animal>  $query
	 */
	public function scopePublished(Builder $query): void
	{
		$query->where('is_published', true);
	}

	/**
	 * Representative value for a measurement (midpoint of min/max), or null if not researched.
	 */
	public function measurementValue(MeasurementKey $key): ?float
	{
		$measurement = $this->measurements->first(fn (AnimalMeasurement $m) => $m->key === $key);

		return $measurement?->representativeValue();
	}

	/**
	 * Effective game value (override first, then derived) for a stat. Defaults to 1 when unknown.
	 */
	public function statValue(Stat $stat): int
	{
		$row = $this->stats->first(fn (AnimalStat $s) => $s->stat === $stat);

		return $row?->effectiveValue() ?? 0;
	}

	/**
	 * @return array<string, int>
	 */
	public function statBlock(): array
	{
		$block = [];

		foreach (Stat::cases() as $stat) {
			$block[$stat->value] = $this->statValue($stat);
		}

		return $block;
	}

	/**
	 * Share of measurement keys that have been researched, 0-100.
	 */
	public function completeness(): int
	{
		$total = count(MeasurementKey::cases());

		return (int) round($this->measurements->count() / $total * 100);
	}
}
