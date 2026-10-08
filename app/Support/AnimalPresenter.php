<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\AnimalFact;
use App\Models\AnimalMeasurement;

/**
 * Shapes animals into arrays for Inertia pages.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalPresenter
{
	/**
	 * Compact card used in draft offers, results and brawl opponents.
	 *
	 * @param  array<string, int>|null  $stats  stat block to show (defaults to the animal's own)
	 * @return array<string, mixed>
	 */
	public static function card(Animal $animal, ?array $stats = null, ?int $factSeed = null): array
	{
		$facts = $animal->facts->filter(fn (AnimalFact $f) => $f->type->value !== 'habitat')->values();
		$fact = null;

		if ($facts->isNotEmpty()) {
			$fact = $facts[($factSeed ?? 0) % $facts->count()];
		}

		return [
			'id' => $animal->id,
			'slug' => $animal->slug,
			'name' => $animal->common_name,
			'scientific_name' => $animal->scientific_name,
			'taxon_class' => $animal->taxon_class,
			'domain' => $animal->domain?->value,
			'diet' => $animal->diet?->label(),
			'image_url' => $animal->image_url,
			'image_credit' => $animal->image_credit,
			'image_licence' => $animal->image_licence,
			'art' => self::art($animal),
			'habitat' => $animal->habitat_summary,
			'conservation' => self::conservation($animal),
			'fact' => $fact ? ['type' => $fact->type->label(), 'body' => $fact->body] : null,
			'stats' => $stats ?? $animal->statBlock(),
		];
	}

	/**
	 * Full encyclopedia entry.
	 *
	 * @return array<string, mixed>
	 */
	public static function profile(Animal $animal): array
	{
		return [
			...self::card($animal),
			'description' => $animal->description,
			'taxon_order' => $animal->taxon_order,
			'taxon_family' => $animal->taxon_family,
			'regions' => $animal->regions ?? [],
			'habitats' => $animal->habitats ?? [],
			'population_trend' => $animal->population_trend,
			'wikipedia_url' => $animal->wikipedia_title ? 'https://en.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $animal->wikipedia_title)) : null,
			'facts' => $animal->facts->map(fn (AnimalFact $f) => [
				'type' => $f->type->label(),
				'body' => $f->body,
				'source' => $f->source?->name,
			])->all(),
			'measurements' => $animal->measurements
				->sortBy(fn (AnimalMeasurement $m) => $m->key->isScore() ? 1 : 0)
				->values()
				->map(fn (AnimalMeasurement $m) => [
					'key' => $m->key->value,
					'label' => $m->key->label(),
					'value' => $m->formatted(),
					'is_score' => $m->key->isScore(),
					'confidence' => $m->confidence->label(),
					'source' => $m->source?->name,
					'source_url' => $m->source?->url,
				])->all(),
			'categories' => $animal->categories->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name])->all(),
		];
	}

	/**
	 * Licence-free silhouette used across the game UI. `taxon` is set when the art shows a close relative.
	 *
	 * @return array{url: string, credit: string|null, licence: string|null, source_url: string|null, taxon: string|null}|null
	 */
	public static function art(Animal $animal): ?array
	{
		return $animal->silhouette_path ? [
			'url' => asset(ltrim($animal->silhouette_path, '/')),
			'credit' => $animal->silhouette_credit,
			'licence' => $animal->silhouette_licence,
			'source_url' => $animal->silhouette_source_url,
			'taxon' => $animal->silhouette_taxon,
		] : null;
	}

	/**
	 * @return array{code: string, label: string, threatened: bool, trend: string|null}|null
	 */
	public static function conservation(Animal $animal): ?array
	{
		$status = $animal->conservation_status;

		return $status ? [
			'code' => $status->value,
			'label' => $status->label(),
			'threatened' => $status->isThreatened(),
			'trend' => $animal->population_trend,
		] : null;
	}
}
