<?php

namespace App\Enums;

/**
 * Main medium an animal moves through. Used for speed context and brawl terrain.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum Domain: string
{
	case Land = 'land';
	case Air = 'air';
	case Water = 'water';
	case Amphibious = 'amphibious';

	public function label(): string
	{
		return ucfirst($this->value);
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
