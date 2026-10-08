<?php

namespace App\Filament\Resources\Animals\Pages;

use App\Filament\Resources\Animals\AnimalResource;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Filament\Resources\Pages\CreateRecord;

/**
 * Creating an animal derives its stats immediately so the Stats tab is populated on the edit page.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CreateAnimal extends CreateRecord
{
	protected static string $resource = AnimalResource::class;

	protected function afterCreate(): void
	{
		app(StatCalculator::class)->recalculateAll();
		app(CategoryAssigner::class)->syncAll();
	}
}
