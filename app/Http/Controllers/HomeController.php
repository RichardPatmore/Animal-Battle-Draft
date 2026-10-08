<?php

namespace App\Http\Controllers;

use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Draft;
use App\Support\AnimalPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page: pick a category to draft from, plus a featured "animal of the day".
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class HomeController extends Controller
{
	public function __invoke(Request $request): Response
	{
		$categories = Category::query()
			->where('is_active', true)
			->withCount(['animals' => fn ($q) => $q->where('is_published', true)])
			->orderBy('sort_order')
			->get()
			->map(fn (Category $c) => [
				'slug' => $c->slug,
				'name' => $c->name,
				'description' => $c->description,
				'icon' => $c->icon,
				'animals_count' => $c->animals_count,
				'size_relative' => $c->size_relative,
				'playable' => $c->animals_count >= count(Stat::cases()) + 2,
			]);

		$recent = Draft::query()
			->where('session_id', $request->session()->getId())
			->latest()
			->with('category')
			->limit(5)
			->get()
			->map(fn (Draft $d) => [
				'id' => $d->public_id,
				'status' => $d->status->value,
				'hybrid_name' => $d->hybrid_name,
				'final_score' => $d->final_score,
				'category' => $d->category->name ?? 'All Animals',
				'created_at' => $d->created_at?->diffForHumans(),
			]);

		return Inertia::render('home', [
			'categories' => $categories,
			'recentDrafts' => $recent,
			'featured' => $this->featured(),
			'stats' => Stat::options(),
		]);
	}

	/**
	 * Same animal for everyone all day.
	 *
	 * @return array<string, mixed>|null
	 */
	private function featured(): ?array
	{
		$ids = Animal::query()->published()->orderBy('id')->pluck('id');

		if ($ids->isEmpty()) {
			return null;
		}

		$day = (int) now()->format('z') + (int) now()->format('Y') * 366;
		$animal = Animal::query()->with(['stats', 'facts'])->whereKey($ids[$day % $ids->count()])->first();

		return $animal ? AnimalPresenter::card($animal, factSeed: $day) : null;
	}
}
