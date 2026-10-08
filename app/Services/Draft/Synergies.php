<?php

namespace App\Services\Draft;

use App\Enums\Stat;

/**
 * Bonus combos awarded when a finished hybrid's stats complement each other.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class Synergies
{
	/**
	 * @return list<array{key: string, label: string, description: string, bonus: int, requires: array<string, int>}>
	 */
	public static function definitions(): array
	{
		return [
			[
				'key' => 'ambush_predator',
				'label' => 'Ambush Predator',
				'description' => 'Hidden until it strikes: Stealth 70+ and Strength 70+.',
				'bonus' => 25,
				'requires' => [Stat::Stealth->value => 70, Stat::Strength->value => 70],
			],
			[
				'key' => 'speed_demon',
				'label' => 'Speed Demon',
				'description' => 'Blistering pace and reflexes: Agility 85+.',
				'bonus' => 25,
				'requires' => [Stat::Agility->value => 85],
			],
			[
				'key' => 'living_tank',
				'label' => 'Living Tank',
				'description' => 'Almost impossible to bring down: Health 75+ and Defence 75+.',
				'bonus' => 25,
				'requires' => [Stat::Health->value => 75, Stat::Defence->value => 75],
			],
			[
				'key' => 'powerhouse',
				'label' => 'Powerhouse',
				'description' => 'Overwhelming offence: Strength 85+.',
				'bonus' => 20,
				'requires' => [Stat::Strength->value => 85],
			],
			[
				'key' => 'mastermind',
				'label' => 'Mastermind',
				'description' => 'Sees everything and outthinks everyone: Intelligence 80+ and Senses 70+.',
				'bonus' => 20,
				'requires' => [Stat::Intelligence->value => 80, Stat::Senses->value => 70],
			],
			[
				'key' => 'all_rounder',
				'label' => 'All-Rounder',
				'description' => 'No weak spots: every stat 50+.',
				'bonus' => 40,
				'requires' => array_fill_keys(array_map(fn (Stat $s) => $s->value, Stat::cases()), 50),
			],
		];
	}

	/**
	 * Synergies a stat block qualifies for.
	 *
	 * @param  array<string, int|null>  $stats
	 * @return list<array{key: string, label: string, description: string, bonus: int}>
	 */
	public static function evaluate(array $stats): array
	{
		$earned = [];

		foreach (self::definitions() as $synergy) {
			foreach ($synergy['requires'] as $stat => $minimum) {
				if (($stats[$stat] ?? 0) < $minimum) {
					continue 2;
				}
			}

			unset($synergy['requires']);
			$earned[] = $synergy;
		}

		return $earned;
	}
}
