<?php

namespace App\Enums;

/**
 * Game stats every animal (and every drafted hybrid) is rated on, each on a 0-100 scale.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum Stat: string
{
	case Strength = 'strength';
	case Health = 'health';
	case Defence = 'defence';
	case Senses = 'senses';
	case Intelligence = 'intelligence';
	case Stealth = 'stealth';
	case Agility = 'agility';

	public function label(): string
	{
		return match ($this) {
			self::Strength => 'Strength',
			self::Health => 'Health',
			self::Defence => 'Defence',
			self::Senses => 'Senses',
			self::Intelligence => 'Intelligence',
			self::Stealth => 'Stealth',
			self::Agility => 'Agility',
		};
	}

	/**
	 * Lucide icon name used by the front end.
	 */
	public function icon(): string
	{
		return match ($this) {
			self::Strength => 'dumbbell',
			self::Health => 'heart-pulse',
			self::Defence => 'shield',
			self::Senses => 'eye',
			self::Intelligence => 'brain',
			self::Stealth => 'ghost',
			self::Agility => 'wind',
		};
	}

	public function description(): string
	{
		return match ($this) {
			self::Strength => 'Power and weapons: mass, bite force, claws, horns and venom.',
			self::Health => 'How much punishment it can take, from size and toughness.',
			self::Defence => 'Armour, shells and spines plus thick skin, fat and blubber.',
			self::Senses => 'Vision, hearing and smell combined.',
			self::Intelligence => 'Problem solving, learning and brain size relative to body.',
			self::Stealth => 'Camouflage, silence and ambush ability.',
			self::Agility => 'Speed, manoeuvrability, reflexes and acceleration.',
		};
	}

	/**
	 * Maps legacy 9-stat blocks (speed, attack) onto the current seven stats.
	 *
	 * @param  array<string, int|float|null>  $block
	 * @return array<string, int>
	 */
	public static function normalizeBlock(array $block): array
	{
		$normalized = [];

		foreach (self::cases() as $stat) {
			$normalized[$stat->value] = (int) ($block[$stat->value] ?? 0);
		}

		if (isset($block['attack'])) {
			$normalized['strength'] = max($normalized['strength'], (int) $block['attack']);
		}

		if (isset($block['speed'])) {
			$normalized['agility'] = max($normalized['agility'], (int) $block['speed']);
		}

		return $normalized;
	}

	/**
	 * @return array<int, array{value: string, label: string, icon: string, description: string}>
	 */
	public static function options(): array
	{
		return array_map(fn (self $stat) => [
			'value' => $stat->value,
			'label' => $stat->label(),
			'icon' => $stat->icon(),
			'description' => $stat->description(),
		], self::cases());
	}
}
