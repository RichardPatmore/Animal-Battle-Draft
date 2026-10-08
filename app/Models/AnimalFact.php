<?php

namespace App\Models;

use App\Enums\FactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An educational fact shown on an animal's card.
 *
 * @property int $id
 * @property int $animal_id
 * @property FactType $type
 * @property string $body
 * @property int|null $source_id
 * @property int $sort_order
 * @property-read Animal $animal
 * @property-read Source|null $source
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['animal_id', 'type', 'body', 'source_id', 'sort_order'])]
class AnimalFact extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'type' => FactType::class,
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
}
