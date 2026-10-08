<?php

namespace App\Services\Draft;

use App\Enums\Stat;

/**
 * Works out a hybrid's stats from the animals drafted into it.
 *
 * The animal picked for a stat supplies OWN_WEIGHT (80%) of that stat. The remaining 20% is shared
 * equally between every other drafted animal's value for the same stat, so the whole team still
 * shapes each stat. With only one animal drafted, it supplies the full value.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class HybridBlender
{
	public const OWN_WEIGHT = 0.8;

	/**
	 * @param  array<int, array{stat: Stat, stats: array<string, int>}>  $picks  each drafted animal, the stat it fills and its full stat block
	 * @return array<string, int|null> stat => blended value, null while the slot is empty
	 */
	public static function blend(array $picks): array
	{
		$hybrid = array_fill_keys(array_map(fn (Stat $s) => $s->value, Stat::cases()), null);

		foreach ($picks as $i => $pick) {
			$key = $pick['stat']->value;
			$own = (float) ($pick['stats'][$key] ?? 0);
			$others = [];

			foreach ($picks as $j => $other) {
				if ($j !== $i) {
					$others[] = (float) ($other['stats'][$key] ?? 0);
				}
			}

			$value = $others === []
				? $own
				: self::OWN_WEIGHT * $own + (1 - self::OWN_WEIGHT) * array_sum($others) / count($others);

			$hybrid[$key] = (int) max(0, min(100, round($value)));
		}

		return $hybrid;
	}
}
