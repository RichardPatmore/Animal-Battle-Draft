<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\MeasurementKey;
use App\Services\Categories\CategoryAssigner;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Category editor with a rule builder for automatic membership.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CategoryForm
{
	public const ICONS = [
		'globe' => 'Globe',
		'crown' => 'Crown',
		'paw-print' => 'Paw print',
		'bird' => 'Bird',
		'turtle' => 'Turtle',
		'fish' => 'Fish',
		'bug' => 'Bug',
		'waves' => 'Waves',
		'skull' => 'Skull',
		'heart-handshake' => 'Heart handshake',
	];

	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make('Details')
					->columnSpanFull()
					->schema([
						Grid::make(3)->schema([
							TextInput::make('name')->required(),
							TextInput::make('slug')->required()->unique(ignoreRecord: true),
							Select::make('icon')->options(self::ICONS),
						]),
						Textarea::make('description')->rows(2),
						Grid::make(3)->schema([
							Toggle::make('is_active')->default(true),
							Toggle::make('size_relative')
								->helperText('Rescale stats within this pool so small animals can compete (e.g. Bugs).'),
							TextInput::make('sort_order')->numeric()->default(0),
						]),
					]),
				Section::make('Membership rules')
					->columnSpanFull()
					->description('Animals matching every rule are added automatically when categories sync. Leave empty for a manual-only pool. For "in" use comma-separated values.')
					->schema([
						Repeater::make('rules')
							->hiddenLabel()
							->columns(3)
							->defaultItems(0)
							->addActionLabel('Add rule')
							->schema([
								Select::make('field')
									->options(self::fieldOptions())
									->searchable()
									->required(),
								Select::make('operator')
									->options(array_combine(CategoryAssigner::OPERATORS, CategoryAssigner::OPERATORS))
									->required(),
								TextInput::make('value')
									->required()
									->formatStateUsing(fn ($state) => match (true) {
										is_array($state) => implode(', ', $state),
										is_bool($state) => $state ? 'true' : 'false',
										default => $state,
									}),
							]),
					]),
			]);
	}

	/**
	 * @return array<string, string>
	 */
	public static function fieldOptions(): array
	{
		$fields = [
			'taxon_class' => 'Class',
			'taxon_order' => 'Order',
			'taxon_family' => 'Family',
			'domain' => 'Domain (land/air/water/amphibious)',
			'diet' => 'Diet',
			'conservation_status' => 'IUCN status code',
			'is_apex_predator' => 'Apex predator (true/false)',
			'is_published' => 'Published (true/false)',
		];

		foreach (MeasurementKey::cases() as $key) {
			$fields['measurement.'.$key->value] = 'Measurement: '.$key->label().' ('.$key->unit().')';
		}

		return $fields;
	}
}
