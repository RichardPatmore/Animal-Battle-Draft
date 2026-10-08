<?php

namespace App\Filament\Resources\Animals;

use App\Filament\Resources\Animals\Pages\CreateAnimal;
use App\Filament\Resources\Animals\Pages\EditAnimal;
use App\Filament\Resources\Animals\Pages\ListAnimals;
use App\Filament\Resources\Animals\Schemas\AnimalForm;
use App\Filament\Resources\Animals\Tables\AnimalsTable;
use App\Models\Animal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Admin CRUD for animals and their research data.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AnimalResource extends Resource
{
	protected static ?string $model = Animal::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

	protected static ?string $recordTitleAttribute = 'common_name';

	protected static ?int $navigationSort = 1;

	public static function form(Schema $schema): Schema
	{
		return AnimalForm::configure($schema);
	}

	public static function table(Table $table): Table
	{
		return AnimalsTable::configure($table);
	}

	public static function getPages(): array
	{
		return [
			'index' => ListAnimals::route('/'),
			'create' => CreateAnimal::route('/create'),
			'edit' => EditAnimal::route('/{record}/edit'),
		];
	}
}
