<?php

namespace App\Models;

use App\Enums\Confidence;
use App\Enums\MeasurementKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One researched real-world measurement for an animal (e.g. top speed).
 *
 * Rows with is_manual set were entered by hand and must never be overwritten by importers.
 *
 * @property int $id
 * @property int $animal_id
 * @property MeasurementKey $key
 * @property float $value_min
 * @property float|null $value_max
 * @property int|null $source_id
 * @property Confidence $confidence
 * @property bool $is_manual
 * @property string|null $notes
 * @property-read Animal $animal
 * @property-read Source|null $source
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['animal_id', 'key', 'value_min', 'value_max', 'source_id', 'confidence', 'is_manual', 'notes'])]
class AnimalMeasurement extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'key' => MeasurementKey::class,
			'confidence' => Confidence::class,
			'value_min' => 'float',
			'value_max' => 'float',
			'is_manual' => 'boolean',
		];
	}

	/**
	 * @return BelongsTo<Animal, $this>
	 */
	public function animal(): BelongsTo
	{
		return $this->belongsTo(Animal::class);
	}

	/**
	 * @return BelongsTo<Source, $this>
	 */
	public function source(): BelongsTo
	{
		return $this->belongsTo(Source::class);
	}

	/**
	 * Midpoint of the range, or the single value when no max is set.
	 */
	public function representativeValue(): float
	{
		if ($this->value_max === null) {
			return $this->value_min;
		}

		return ($this->value_min + $this->value_max) / 2;
	}

	/**
	 * Human readable value, e.g. "50-70 km/h".
	 */
	public function formatted(): string
	{
		$format = static function (float $v): string {
			$decimals = match (true) {
				$v >= 10 => 0,
				$v >= 0.01 => 2,
				default => 6,
			};
			$text = number_format($v, $decimals, '.', ',');

			return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
		};
		$value = $format($this->value_min);

		if ($this->value_max !== null && $this->value_max != $this->value_min) {
			$value .= '-'.$format($this->value_max);
		}

		return $value.' '.$this->key->unit();
	}
}
