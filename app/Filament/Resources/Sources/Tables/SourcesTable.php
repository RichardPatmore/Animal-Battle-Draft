<?php

namespace App\Filament\Resources\Sources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Citation list with how many values and facts rely on each source.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class SourcesTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->modifyQueryUsing(fn (Builder $query) => $query->withCount(['measurements', 'facts']))
			->defaultSort('name')
			->columns([
				TextColumn::make('name')->searchable()->sortable(),
				TextColumn::make('url')
					->searchable()
					->limit(50)
					->url(fn ($record) => $record->url, shouldOpenInNewTab: true),
				TextColumn::make('measurements_count')->label('Measurements')->badge()->sortable(),
				TextColumn::make('facts_count')->label('Facts')->badge()->sortable(),
				TextColumn::make('retrieved_at')->since()->sortable(),
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
