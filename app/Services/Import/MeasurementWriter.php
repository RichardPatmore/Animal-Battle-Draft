<?php

namespace App\Services\Import;

use App\Enums\Confidence;
use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Models\AnimalMeasurement;
use App\Models\Source;

/**
 * Writes imported measurements while protecting rows entered by hand.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class MeasurementWriter
{
	/**
	 * Upserts a measurement. Returns false when skipped because a manual value exists.
	 */
	public function write(Animal $animal, MeasurementKey $key, float $min, ?float $max, Source $source, Confidence $confidence = Confidence::Medium): bool
	{
		$existing = AnimalMeasurement::query()
			->where('animal_id', $animal->id)
			->where('key', $key->value)
			->first();

		if ($existing?->is_manual) {
			return false;
		}

		if ($max !== null && abs($max - $min) < 1e-9) {
			$max = null;
		}

		AnimalMeasurement::query()->updateOrCreate(
			['animal_id' => $animal->id, 'key' => $key->value],
			[
				'value_min' => $min,
				'value_max' => $max,
				'source_id' => $source->id,
				'confidence' => $confidence,
				'is_manual' => false,
			],
		);

		return true;
	}
}
