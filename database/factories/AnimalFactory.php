<?php

namespace Database\Factories;

use App\Enums\ConservationStatus;
use App\Enums\Diet;
use App\Enums\Domain;
use App\Enums\Stat;
use App\Models\Animal;
use App\Models\AnimalStat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Animal>
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalFactory extends Factory
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		$name = ucfirst(fake()->unique()->lexify('animal????'));

		return [
			'slug' => Str::slug($name),
			'common_name' => $name,
			'scientific_name' => ucfirst(fake()->lexify('genus????')).' '.fake()->lexify('species????'),
			'taxon_class' => 'Mammalia',
			'domain' => Domain::Land,
			'diet' => Diet::Carnivore,
			'conservation_status' => ConservationStatus::LeastConcern,
			'description' => 'Test animal for automated suites.',
			'is_published' => true,
		];
	}

	/**
	 * Gives the animal a full stat block, either fixed values or random ones.
	 *
	 * @param  array<string, int>  $values
	 */
	public function withStats(array $values = []): static
	{
		return $this->afterCreating(function (Animal $animal) use ($values) {
			foreach (Stat::cases() as $stat) {
				AnimalStat::query()->create([
					'animal_id' => $animal->id,
					'stat' => $stat,
					'derived_value' => $values[$stat->value] ?? fake()->numberBetween(1, 100),
				]);
			}
		});
	}

	public function unpublished(): static
	{
		return $this->state(['is_published' => false]);
	}
}
