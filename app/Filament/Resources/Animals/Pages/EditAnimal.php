<?php

namespace App\Filament\Resources\Animals\Pages;

use App\Filament\Resources\Animals\AnimalResource;
use App\Models\Animal;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Editing an animal re-derives every stat (percentiles depend on the whole pool) and re-syncs categories.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class EditAnimal extends EditRecord
{
	protected static string $resource = AnimalResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Action::make('view')
				->label('View in encyclopedia')
				->icon('heroicon-o-arrow-top-right-on-square')
				->url(fn (Animal $record) => route('animals.show', $record))
				->openUrlInNewTab()
				->visible(fn (Animal $record) => $record->is_published),
			DeleteAction::make(),
		];
	}

	protected function afterSave(): void
	{
		app(StatCalculator::class)->recalculateAll();
		app(CategoryAssigner::class)->syncAll();

		$this->refreshFormData(['stats']);
	}
}
