<?php

namespace App\Models;

use App\Enums\DraftStatus;
use App\Enums\Stat;
use App\Services\Draft\HybridBlender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A single solo draft run that builds one hybrid animal, owned by a browser session.
 *
 * @property int $id
 * @property string $public_id
 * @property string $session_id
 * @property int|null $category_id
 * @property int $seed
 * @property DraftStatus $status
 * @property list<int>|null $current_offer
 * @property int|null $final_score
 * @property string|null $hybrid_name
 * @property list<array{key: string, label: string, description: string, bonus: int}>|null $synergies
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property-read Category|null $category
 * @property-read Collection<int, DraftPick> $picks
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['session_id', 'category_id', 'seed', 'status', 'current_offer', 'final_score', 'hybrid_name', 'synergies', 'completed_at'])]
class Draft extends Model
{
	use HasUlids;

	/** Placeholder name until the player names their hybrid on the result screen. */
	public const DEFAULT_HYBRID_NAME = 'Creature';

	/**
	 * @return list<string>
	 */
	public function uniqueIds(): array
	{
		return ['public_id'];
	}

	public function getRouteKeyName(): string
	{
		return 'public_id';
	}

	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'status' => DraftStatus::class,
			'current_offer' => 'array',
			'synergies' => 'array',
			'completed_at' => 'datetime',
		];
	}

	/**
	 * @return BelongsTo<Category, $this>
	 */
	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class);
	}

	/**
	 * @return HasMany<DraftPick, $this>
	 */
	public function picks(): HasMany
	{
		return $this->hasMany(DraftPick::class)->orderBy('round');
	}

	public function isComplete(): bool
	{
		return $this->status === DraftStatus::Completed;
	}

	/**
	 * Stats that have not been assigned an animal yet.
	 *
	 * @return list<Stat>
	 */
	public function openStats(): array
	{
		$taken = $this->picks->map(fn (DraftPick $pick) => $pick->stat)->all();

		return array_values(array_filter(Stat::cases(), fn (Stat $stat) => ! in_array($stat, $taken, true)));
	}

	/**
	 * The hybrid's stat block so far, blended across every drafted animal. Unfilled stats are null.
	 *
	 * @see HybridBlender
	 *
	 * @return array<string, int|null>
	 */
	public function hybridStats(): array
	{
		return HybridBlender::blend($this->picks->map(fn (DraftPick $p) => [
			'stat' => $p->stat,
			'stats' => $p->statBlock(),
		])->all());
	}
}
