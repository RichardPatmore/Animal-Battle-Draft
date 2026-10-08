<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Services\Categories\CategoryAssigner;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Re-applies the category's rules after every save.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class EditCategory extends EditRecord
{
	protected static string $resource = CategoryResource::class;

	protected function getHeaderActions(): array
	{
		return [
			DeleteAction::make(),
		];
	}

	protected function afterSave(): void
	{
		/** @var Category $category */
		$category = $this->getRecord();
		$count = app(CategoryAssigner::class)->sync($category);

		Notification::make()->title("{$category->name} now has {$count} animals")->success()->send();
	}
}
