<?php

namespace App\Models;

use App\Enums\Stat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A game stat for an animal: the value derived from measurements plus an optional manual override.
 *
 * @property int $id
 * @property int $animal_id
 * @property Stat $stat
 * @property int|null $derived_value
 * @property int|null $override_value
 * @property array<string, mixed>|null $breakdown
 * @property string|null $notes
 * @property-read Animal $animal
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['animal_id', 'stat', 'derived_value', 'override_value', 'breakdown', 'notes'])]
class AnimalStat extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'stat' => Stat::class,
			'derived_value' => 'integer',
			'override_value' => 'integer',
			'breakdown' => 'array',
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
	 * The value the game uses: the manual override when set, otherwise the derived value.
	 */
	public function effectiveValue(): ?int
	{
		return $this->override_value ?? $this->derived_value;
	}
}
