<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds categories, the starter animal list and a local admin user.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

	public function run(): void
	{
		$this->call([
			CategorySeeder::class,
			AnimalSeeder::class,
		]);

		User::query()->firstOrCreate(
			['email' => 'admin@example.com'],
			['name' => 'Admin', 'password' => 'password', 'email_verified_at' => now()],
		);
	}
}
