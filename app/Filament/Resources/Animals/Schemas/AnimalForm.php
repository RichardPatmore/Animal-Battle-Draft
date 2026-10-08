<?php

namespace App\Filament\Resources\Animals\Schemas;

use App\Enums\Confidence;
use App\Enums\ConservationStatus;
use App\Enums\Diet;
use App\Enums\Domain;
use App\Enums\FactType;
use App\Enums\MeasurementKey;
use App\Enums\Stat;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Animal editor: profile, researched measurements, stat overrides, facts and categories.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Tabs::make('Animal')
					->columnSpanFull()
					->persistTabInQueryString()
					->tabs([
						self::profileTab(),
						self::measurementsTab(),
						self::statsTab(),
						self::factsTab(),
						Tab::make('Categories')
							->icon('heroicon-o-tag')
							->schema([
								CheckboxList::make('categories')
									->relationship('categories', 'name')
									->columns(3)
									->helperText('Rule-based categories are re-applied on save, so ticking here is only needed for manual-only pools.'),
							]),
					]),
			]);
	}

	private static function profileTab(): Tab
	{
		return Tab::make('Profile')
			->icon('heroicon-o-identification')
			->schema([
				Grid::make(3)->schema([
					TextInput::make('common_name')
						->required()
						->live(onBlur: true)
						->afterStateUpdated(function ($state, callable $set, callable $get) {
							if (blank($get('slug'))) {
								$set('slug', Str::slug((string) $state));
							}
						}),
					TextInput::make('slug')
						->required()
						->unique(ignoreRecord: true),
					TextInput::make('scientific_name')
						->helperText('Used by importers to find the animal.'),
				]),
				Grid::make(4)->schema([
					Select::make('domain')->options(Domain::selectOptions()),
					Select::make('diet')->options(Diet::selectOptions()),
					Select::make('conservation_status')
						->label('IUCN status')
						->options(ConservationStatus::selectOptions()),
					TextInput::make('population_trend'),
				]),
				Grid::make(3)->schema([
					TextInput::make('taxon_class')->helperText('e.g. Mammalia, Aves, Insecta'),
					TextInput::make('taxon_order'),
					TextInput::make('taxon_family'),
				]),
				Grid::make(2)->schema([
					Toggle::make('is_published')
						->helperText('Only published animals appear in drafts and the encyclopedia.'),
					Toggle::make('is_apex_predator'),
				]),
				Textarea::make('description')->rows(4)->columnSpanFull(),
				Textarea::make('habitat_summary')->rows(2)->columnSpanFull(),
				Grid::make(2)->schema([
					TagsInput::make('regions')->placeholder('Africa, Asia…'),
					TagsInput::make('habitats')->placeholder('Savanna, Forest…'),
				]),
				Section::make('Image')
					->collapsible()
					->schema([
						TextInput::make('image_url')->url()->maxLength(1024)->columnSpanFull(),
						Grid::make(2)->schema([
							TextInput::make('image_credit'),
							TextInput::make('image_licence'),
						]),
						TextInput::make('silhouette_path')
							->helperText('Same-origin SVG used as game art, e.g. /silhouettes/tiger.svg. Must be licence-free (CC0 / public domain).')
							->maxLength(255)
							->columnSpanFull(),
						Grid::make(2)->schema([
							TextInput::make('silhouette_credit'),
							TextInput::make('silhouette_licence'),
							TextInput::make('silhouette_source_url')->url(),
							TextInput::make('silhouette_taxon')->helperText('Set when the art shows a related species.'),
						]),
					]),
				Section::make('External IDs')
					->collapsible()
					->collapsed()
					->schema([
						Grid::make(3)->schema([
							TextInput::make('wikidata_id'),
							TextInput::make('wikipedia_title'),
							TextInput::make('gbif_key')->numeric(),
						]),
					]),
			]);
	}

	private static function measurementsTab(): Tab
	{
		return Tab::make('Measurements')
			->icon('heroicon-o-beaker')
			->schema([
				Repeater::make('measurements')
					->relationship()
					->hiddenLabel()
					->columns(6)
					->itemLabel(fn (array $state): ?string => isset($state['key'])
						? (MeasurementKey::tryFrom((string) $state['key'])?->label() ?? $state['key'])
						: null)
					->collapsible()
					->addActionLabel('Add measurement')
					->schema([
						Select::make('key')
							->options(MeasurementKey::selectOptions())
							->required()
							->distinct()
							->disableOptionsWhenSelectedInSiblingRepeaterItems()
							->columnSpan(2),
						TextInput::make('value_min')
							->label('Value / min')
							->numeric()
							->required(),
						TextInput::make('value_max')
							->label('Max (optional)')
							->numeric(),
						Select::make('confidence')
							->options(Confidence::selectOptions())
							->default(Confidence::Medium->value)
							->required(),
						Toggle::make('is_manual')
							->label('Lock')
							->default(true)
							->inline(false)
							->helperText('Importers never overwrite locked values.'),
						Select::make('source_id')
							->relationship('source', 'name')
							->searchable()
							->preload()
							->createOptionForm([
								TextInput::make('name')->required(),
								TextInput::make('url')->url(),
							])
							->columnSpan(3),
						TextInput::make('notes')->columnSpan(3),
					]),
			]);
	}

	private static function statsTab(): Tab
	{
		return Tab::make('Stats')
			->icon('heroicon-o-chart-bar')
			->schema([
				Repeater::make('stats')
					->relationship()
					->hiddenLabel()
					->addable(false)
					->deletable(false)
					->reorderable(false)
					->columns(6)
					->itemLabel(fn (array $state): ?string => isset($state['stat'])
						? (Stat::tryFrom($state['stat'] instanceof Stat ? $state['stat']->value : (string) $state['stat'])?->label())
						: null)
					->schema([
						TextInput::make('derived_value')
							->label('Derived')
							->disabled()
							->dehydrated(false),
						TextInput::make('override_value')
							->label('Override')
							->numeric()
							->minValue(0)
							->maxValue(100)
							->helperText('Leave blank to use the derived value.'),
						Textarea::make('breakdown')
							->label('How it was derived')
							->disabled()
							->dehydrated(false)
							->rows(5)
							->formatStateUsing(fn ($state) => self::describeBreakdown(is_array($state) ? $state : null))
							->columnSpan(3),
						Textarea::make('notes')->rows(3),
					]),
			]);
	}

	private static function factsTab(): Tab
	{
		return Tab::make('Facts')
			->icon('heroicon-o-light-bulb')
			->schema([
				Repeater::make('facts')
					->relationship()
					->hiddenLabel()
					->orderColumn('sort_order')
					->columns(4)
					->collapsible()
					->addActionLabel('Add fact')
					->schema([
						Select::make('type')
							->options(FactType::selectOptions())
							->default(FactType::Fun->value)
							->required(),
						Select::make('source_id')
							->relationship('source', 'name')
							->searchable()
							->preload(),
						Textarea::make('body')
							->required()
							->rows(2)
							->columnSpan(2),
					]),
			]);
	}

	/**
	 * Turns the stored breakdown JSON into readable lines for the admin.
	 *
	 * @param  array<string, mixed>|null  $breakdown
	 */
	public static function describeBreakdown(?array $breakdown): string
	{
		if (! $breakdown) {
			return 'Not calculated yet.';
		}

		$inputs = is_array($breakdown['inputs'] ?? null) ? $breakdown['inputs'] : [];

		if ($breakdown['missing_data'] ?? false) {
			return 'Missing data: add one of these measurements: '.collect($inputs)->pluck('label')->implode(', ');
		}

		$lines = collect($inputs)->map(function (array $input) {
			if ($input['value'] === null) {
				return "{$input['label']}: missing";
			}

			$inverted = ($input['inverted'] ?? false) ? ', inverted' : '';

			return sprintf('%s: %s %s, normalised %.2f, weight %.2f%s', $input['label'], $input['value'], $input['unit'] ?? '', $input['normalised'], $input['weight'], $inverted);
		});

		$lines->push(sprintf('Raw %.2f, percentile %.2f (pool of %d)', $breakdown['raw'] ?? 0, $breakdown['percentile'] ?? 0, $breakdown['pool_size'] ?? 0));

		return $lines->implode("\n");
	}
}
