<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Services\Categories\CategoryAssigner;
use Filament\Resources\Pages\CreateRecord;

/**
 * Applies the new category's rules straight away.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CreateCategory extends CreateRecord
{
	protected static string $resource = CategoryResource::class;

	protected function afterCreate(): void
	{
		/** @var Category $category */
		$category = $this->getRecord();
		app(CategoryAssigner::class)->sync($category);
	}
}
