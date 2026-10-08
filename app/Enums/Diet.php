<?php

namespace App\Enums;

/**
 * Primary diet of an animal.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum Diet: string
{
	case Carnivore = 'carnivore';
	case Herbivore = 'herbivore';
	case Omnivore = 'omnivore';
	case Insectivore = 'insectivore';
	case Piscivore = 'piscivore';
	case FilterFeeder = 'filter_feeder';
	case Scavenger = 'scavenger';

	public function label(): string
	{
		return match ($this) {
			self::Carnivore => 'Carnivore',
			self::Herbivore => 'Herbivore',
			self::Omnivore => 'Omnivore',
			self::Insectivore => 'Insectivore',
			self::Piscivore => 'Piscivore',
			self::FilterFeeder => 'Filter feeder',
			self::Scavenger => 'Scavenger',
		};
	}

	/**
	 * @return array<string, string>
	 */
	public static function selectOptions(): array
	{
		$options = [];

		foreach (self::cases() as $case) {
			$options[$case->value] = $case->label();
		}

		return $options;
	}
}
