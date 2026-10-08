<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Category list ordered as players see it on the home page.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CategoriesTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->modifyQueryUsing(fn (Builder $query) => $query->withCount([
				'animals',
				'animals as published_count' => fn (Builder $q) => $q->where('is_published', true),
			]))
			->defaultSort('sort_order')
			->reorderable('sort_order')
			->columns([
				TextColumn::make('name')->description(fn ($record) => $record->description)->searchable(),
				TextColumn::make('published_count')->label('Published animals')->badge(),
				TextColumn::make('animals_count')->label('Total animals'),
				IconColumn::make('size_relative')->boolean()->label('Size-relative'),
				TextColumn::make('rule_count')
					->label('Rules')
					->state(fn ($record) => count($record->rules ?? [])),
				ToggleColumn::make('is_active')->label('Active'),
			])
			->recordActions([
				EditAction::make(),
			])
			->toolbarActions([
				BulkActionGroup::make([
					DeleteBulkAction::make(),
				]),
			]);
	}
}
