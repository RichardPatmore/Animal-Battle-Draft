<?php

namespace App\Filament\Resources\Animals\Pages;

use App\Filament\Resources\Animals\AnimalResource;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

/**
 * Animal list with a header action to recalculate every stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class ListAnimals extends ListRecords
{
	protected static string $resource = AnimalResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Action::make('recalculate')
				->label('Recalculate stats')
				->icon('heroicon-o-arrow-path')
				->color('gray')
				->action(function () {
					$count = app(StatCalculator::class)->recalculateAll();
					app(CategoryAssigner::class)->syncAll();

					Notification::make()->title("Recalculated stats for {$count} animals")->success()->send();
				}),
			CreateAction::make(),
		];
	}
}
