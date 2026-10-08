<?php

namespace App\Filament\Resources\Sources\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Citation editor.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class SourceForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				TextInput::make('name')->required(),
				TextInput::make('url')->url(),
				DateTimePicker::make('retrieved_at'),
				Textarea::make('notes')->columnSpanFull(),
			]);
	}
}
