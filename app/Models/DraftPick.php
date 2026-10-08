<?php

namespace App\Models;

use App\Enums\Stat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One round of a draft: the animals offered, the one chosen, and which stat slot it filled.
 *
 * @property int $id
 * @property int $draft_id
 * @property int $round
 * @property list<int> $offered_animal_ids
 * @property int $animal_id
 * @property Stat $stat
 * @property int $value the drafted animal's own value for the stat it fills
 * @property array<string, int>|null $animal_stats the drafted animal's full stat block on this draft's scale
 * @property-read Draft $draft
 * @property-read Animal $animal
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['draft_id', 'round', 'offered_animal_ids', 'animal_id', 'stat', 'value', 'animal_stats'])]
class DraftPick extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'stat' => Stat::class,
			'offered_animal_ids' => 'array',
			'value' => 'integer',
			'animal_stats' => 'array',
		];
	}

	/**
	 * Full stat block for blending. Older picks without a snapshot only know their own stat.
	 *
	 * @return array<string, int>
	 */
	public function statBlock(): array
	{
		$block = $this->animal_stats ?? [$this->stat->value => $this->value];

		return Stat::normalizeBlock($block);
	}

	/**
	 * @return BelongsTo<Draft, $this>
	 */
	public function draft(): BelongsTo
	{
		return $this->belongsTo(Draft::class);
	}

	/**
	 * @return BelongsTo<Animal, $this>
	 */
	public function animal(): BelongsTo
	{
		return $this->belongsTo(Animal::class);
	}
}
