<?php

namespace App\Enums;

/**
 * How much a measurement can be trusted, shown to players next to the value.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum Confidence: string
{
	case Low = 'low';
	case Medium = 'medium';
	case High = 'high';

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
