import type { StatBlock, StatKey } from '@/types/game';

export type BlendPick = { stat: StatKey; stats: Partial<StatBlock> };

/**
 * Mirrors App\Services\Draft\HybridBlender: the animal picked for a stat supplies `ownWeight`
 * of it, and the rest is shared equally between every other drafted animal's value for that stat.
 */
export function blendHybrid(
	picks: BlendPick[],
	statKeys: StatKey[],
	ownWeight: number,
): Record<StatKey, number | null> {
	const hybrid = Object.fromEntries(
		statKeys.map((key) => [key, null]),
	) as Record<StatKey, number | null>;

	picks.forEach((pick, i) => {
		const own = pick.stats[pick.stat] ?? 0;
		const others = picks
			.filter((_, j) => j !== i)
			.map((other) => other.stats[pick.stat] ?? 0);
		const value =
			others.length === 0
				? own
				: ownWeight * own +
					((1 - ownWeight) * others.reduce((a, b) => a + b, 0)) /
						others.length;

		hybrid[pick.stat] = Math.max(0, Math.min(100, Math.round(value)));
	});

	return hybrid;
}
