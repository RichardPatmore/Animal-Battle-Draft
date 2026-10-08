<?php

namespace App\Http\Controllers;

use App\Enums\ConservationStatus;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\Category;
use App\Support\AnimalPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Educational encyclopedia of every published animal.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalController extends Controller
{
	public function index(Request $request): Response
	{
		$search = trim((string) $request->query('search', ''));
		$categorySlug = (string) $request->query('category', '');

		$animals = Animal::query()
			->published()
			->with(['stats', 'facts'])
			->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
				->where('common_name', 'like', "%{$search}%")
				->orWhere('scientific_name', 'like', "%{$search}%")))
			->when($categorySlug !== '', fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $categorySlug)))
			->orderBy('common_name')
			->get()
			->map(fn (Animal $a) => AnimalPresenter::card($a, factSeed: $a->id))
			->all();

		return Inertia::render('animals/index', [
			'animals' => $animals,
			'filters' => ['search' => $search, 'category' => $categorySlug],
			'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(['slug', 'name']),
			'stats' => Stat::options(),
		]);
	}

	public function show(Animal $animal): Response
	{
		abort_unless($animal->is_published, 404);

		$animal->load(['stats', 'facts.source', 'measurements.source', 'categories']);

		return Inertia::render('animals/show', [
			'animal' => AnimalPresenter::profile($animal),
			'stats' => Stat::options(),
			'statuses' => array_map(fn (ConservationStatus $s) => ['code' => $s->value, 'label' => $s->label()], [
				ConservationStatus::LeastConcern,
				ConservationStatus::NearThreatened,
				ConservationStatus::Vulnerable,
				ConservationStatus::Endangered,
				ConservationStatus::CriticallyEndangered,
				ConservationStatus::ExtinctInTheWild,
				ConservationStatus::Extinct,
			]),
		]);
	}
}
