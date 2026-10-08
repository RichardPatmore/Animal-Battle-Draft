<?php

namespace App\Filament\Resources\Animals\Tables;

use App\Enums\ConservationStatus;
use App\Enums\MeasurementKey;
use App\Models\Animal;
use App\Services\Animals\StarterDataImporter;
use App\Services\Categories\CategoryAssigner;
use App\Services\Stats\StatCalculator;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Animal list with data-completeness tracking for manual research.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalsTable
{
	public static function configure(Table $table): Table
	{
		$totalKeys = count(MeasurementKey::cases());

		return $table
			->modifyQueryUsing(fn (Builder $query) => $query->withCount('measurements'))
			->defaultSort('common_name')
			->columns([
				ImageColumn::make('image_url')->label('')->circular(),
				TextColumn::make('common_name')
					->label('Animal')
					->description(fn (Animal $record) => $record->scientific_name)
					->searchable(['common_name', 'scientific_name'])
					->sortable(),
				TextColumn::make('taxon_class')->label('Class')->sortable()->toggleable(),
				TextColumn::make('conservation_status')
					->label('IUCN')
					->badge()
					->formatStateUsing(fn (?ConservationStatus $state) => $state?->value)
					->tooltip(fn (Animal $record) => $record->conservation_status?->label())
					->color(fn (?ConservationStatus $state) => $state?->color() ?? 'gray')
					->sortable(),
				TextColumn::make('measurements_count')
					->label('Completeness')
					->formatStateUsing(fn (int $state) => round($state / $totalKeys * 100).'%')
					->color(fn (int $state) => match (true) {
						$state >= $totalKeys * 0.8 => 'success',
						$state >= $totalKeys * 0.5 => 'warning',
						default => 'danger',
					})
					->badge()
					->sortable(),
				ToggleColumn::make('is_published')->label('Published'),
				TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
			])
			->filters([
				TernaryFilter::make('is_published')->label('Published'),
				SelectFilter::make('taxon_class')
					->label('Class')
					->options(fn () => Animal::query()->whereNotNull('taxon_class')->distinct()->orderBy('taxon_class')->pluck('taxon_class', 'taxon_class')->all()),
				SelectFilter::make('categories')
					->relationship('categories', 'name')
					->multiple()
					->preload(),
				SelectFilter::make('missing_measurement')
					->label('Missing measurement')
					->options(MeasurementKey::selectOptions())
					->query(fn (Builder $query, array $data) => $query->when(
						$data['value'] ?? null,
						fn (Builder $q, string $key) => $q->whereDoesntHave('measurements', fn (Builder $m) => $m->where('key', $key)),
					)),
				Filter::make('incomplete')
					->label('Under 50% complete')
					->query(fn (Builder $query) => $query->has('measurements', '<', (int) ceil($totalKeys / 2))),
				Filter::make('unlocked_estimates')
					->label('Has unverified starter estimates')
					->query(fn (Builder $query) => $query->whereHas('measurements', fn (Builder $m) => $m
						->where('is_manual', false)
						->whereHas('source', fn (Builder $s) => $s->where('name', StarterDataImporter::SOURCE_NAME)))),
			])
			->recordActions([
				EditAction::make(),
			])
			->toolbarActions([
				BulkActionGroup::make([
					BulkAction::make('publish')
						->icon('heroicon-o-eye')
						->action(fn (Collection $records) => self::setPublished($records, true))
						->deselectRecordsAfterCompletion(),
					BulkAction::make('unpublish')
						->icon('heroicon-o-eye-slash')
						->action(fn (Collection $records) => self::setPublished($records, false))
						->deselectRecordsAfterCompletion(),
					DeleteBulkAction::make(),
				]),
			]);
	}

	/**
	 * @param  Collection<int, Animal>  $records
	 */
	private static function setPublished(Collection $records, bool $published): void
	{
		Animal::query()->whereKey($records->modelKeys())->update(['is_published' => $published]);
		app(StatCalculator::class)->recalculateAll();
		app(CategoryAssigner::class)->syncAll();

		Notification::make()
			->title(($published ? 'Published ' : 'Unpublished ').$records->count().' animals')
			->success()
			->send();
	}
}
