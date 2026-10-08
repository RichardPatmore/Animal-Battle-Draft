<?php

namespace App\Services\Draft;

use App\Enums\DraftStatus;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Draft;
use App\Models\DraftPick;
use App\Services\Stats\StatCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Runs a solo pick-one-of-three draft.
 *
 * Each round offers OFFER_SIZE animals from the category pool. The player picks one and assigns it
 * to a stat slot they haven't filled yet. Each pick stores the animal's full stat block so the
 * hybrid's stats can be blended across the whole team (see {@see HybridBlender}).
 * Offers are seeded (draft seed + round) so the same seed always produces the same draft.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class DraftService
{
	public const OFFER_SIZE = 3;

	/** @var array<string, array<int, array<string, int>>> */
	private array $blockCache = [];

	public function __construct(
		private readonly StatCalculator $calculator,
		private readonly HybridNamer $namer,
	) {}

	/**
	 * @throws InvalidArgumentException when the pool is too small to fill every stat
	 */
	public function start(string $sessionId, ?Category $category = null, ?int $seed = null): Draft
	{
		$poolSize = $this->poolQuery($category)->count();
		$needed = count(Stat::cases()) + self::OFFER_SIZE - 1;

		if ($poolSize < $needed) {
			throw new InvalidArgumentException("This category needs at least {$needed} published animals (it has {$poolSize}).");
		}

		$draft = Draft::query()->create([
			'session_id' => $sessionId,
			'category_id' => $category?->id,
			'seed' => $seed ?? random_int(1, 2_000_000_000),
			'status' => DraftStatus::InProgress,
		]);

		$draft->current_offer = $this->generateOffer($draft);
		$draft->save();

		return $draft;
	}

	/**
	 * Picks an offered animal into an open stat slot. Completes the draft after the final slot.
	 *
	 * @throws InvalidArgumentException on an invalid pick
	 */
	public function pick(Draft $draft, int $animalId, Stat $stat): Draft
	{
		$draft->loadMissing('picks', 'category');

		if ($draft->status !== DraftStatus::InProgress) {
			throw new InvalidArgumentException('This draft is already finished.');
		}

		if (! in_array($animalId, $draft->current_offer ?? [], true)) {
			throw new InvalidArgumentException('That animal is not on offer this round.');
		}

		if (! in_array($stat, $draft->openStats(), true)) {
			throw new InvalidArgumentException("{$stat->label()} has already been filled.");
		}

		$blocks = $this->statBlocks($draft->category);

		DB::transaction(function () use ($draft, $animalId, $stat, $blocks) {
			DraftPick::query()->create([
				'draft_id' => $draft->id,
				'round' => $draft->picks->count() + 1,
				'offered_animal_ids' => $draft->current_offer,
				'animal_id' => $animalId,
				'stat' => $stat,
				'value' => $blocks[$animalId][$stat->value] ?? 0,
				'animal_stats' => $blocks[$animalId] ?? null,
			]);

			$draft->load('picks.animal');

			if ($draft->openStats() === []) {
				$this->finish($draft);
			} else {
				$draft->current_offer = $this->generateOffer($draft);
				$draft->save();
			}
		});

		return $draft;
	}

	/**
	 * Scores the completed hybrid, awards synergies and sets the default creature name.
	 */
	public function finish(Draft $draft): Draft
	{
		$draft->loadMissing('picks.animal');
		$stats = $draft->hybridStats();
		$synergies = Synergies::evaluate($stats);

		$draft->fill([
			'status' => DraftStatus::Completed,
			'current_offer' => null,
			'synergies' => $synergies,
			'final_score' => array_sum(array_map('intval', $stats)) + array_sum(array_column($synergies, 'bonus')),
			'hybrid_name' => Draft::DEFAULT_HYBRID_NAME,
			'completed_at' => now(),
		])->save();

		return $draft;
	}

	/**
	 * Animals on offer this round, with their stats on this draft's scale.
	 *
	 * @return Collection<int, Animal>
	 */
	public function offeredAnimals(Draft $draft): Collection
	{
		$ids = $draft->current_offer ?? [];

		return Animal::query()
			->whereIn('id', $ids)
			->with(['stats', 'facts'])
			->get()
			->sortBy(fn (Animal $a) => array_search($a->id, $ids, true))
			->values();
	}

	/**
	 * Effective stat blocks for every animal in a category pool. Size-relative categories are
	 * rescaled within the pool so small animals can still score highly.
	 *
	 * @return array<int, array<string, int>>
	 */
	public function statBlocks(?Category $category): array
	{
		$cacheKey = $category->slug ?? '*';

		if (isset($this->blockCache[$cacheKey])) {
			return $this->blockCache[$cacheKey];
		}

		$blocks = $this->poolQuery($category)
			->with('stats')
			->get()
			->mapWithKeys(fn (Animal $a) => [$a->id => $a->statBlock()])
			->all();

		if ($category?->size_relative) {
			$blocks = $this->calculator->rescaleWithinPool($blocks);
		}

		return $this->blockCache[$cacheKey] = $blocks;
	}

	/**
	 * @return list<int>
	 */
	private function generateOffer(Draft $draft): array
	{
		$draft->loadMissing('picks', 'category');

		$pickedIds = $draft->picks->pluck('animal_id')->all();
		$poolIds = $this->poolQuery($draft->category)
			->whereNotIn('id', $pickedIds)
			->orderBy('id')
			->pluck('id')
			->all();

		$round = $draft->picks->count() + 1;
		$randomizer = new Randomizer(new Mt19937($draft->seed + $round * 7919));
		$shuffled = $randomizer->shuffleArray($poolIds);

		return array_map('intval', array_slice($shuffled, 0, self::OFFER_SIZE));
	}

	/**
	 * @return Builder<Animal>
	 */
	private function poolQuery(?Category $category)
	{
		$query = Animal::query()->published();

		if ($category) {
			$query->whereHas('categories', fn ($q) => $q->whereKey($category->id));
		}

		return $query;
	}
}
