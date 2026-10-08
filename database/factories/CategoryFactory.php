<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CategoryFactory extends Factory
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		$name = ucfirst($this->faker->unique()->word());

		return [
			'slug' => Str::slug($name),
			'name' => $name,
			'is_active' => true,
		];
	}
}
