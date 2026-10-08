<?php

namespace App\Enums;

/**
 * Kinds of educational facts shown on animal cards.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum FactType: string
{
	case Fun = 'fun';
	case Habitat = 'habitat';
	case Conservation = 'conservation';
	case Record = 'record';
	case Behaviour = 'behaviour';
	case Diet = 'diet';

	public function label(): string
	{
		return match ($this) {
			self::Fun => 'Fun fact',
			self::Habitat => 'Habitat',
			self::Conservation => 'Conservation',
			self::Record => 'Record breaker',
			self::Behaviour => 'Behaviour',
			self::Diet => 'Diet',
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
