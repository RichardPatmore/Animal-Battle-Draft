import type { ReactNode } from 'react';
import { NamedIcon } from '@/components/game/stat-icon';
import { cn } from '@/lib/utils';
import type { StatOption } from '@/types/game';

const HUE_STOPS: [number, number][] = [
	[0, 0],
	[20, 0],
	[40, 40],
	[60, 58],
	[80, 120],
	[100, 120],
];

/**
 * Continuous red → yellow → green colour for a 0-100 stat value: low is red, 40-60 is yellowish
 * and 80+ is full green.
 */
export function statColour(value: number | null | undefined): string {
	if (value === null || value === undefined) {
		return 'var(--color-muted-foreground)';
	}

	const v = Math.max(0, Math.min(100, value));
	let hue = 120;

	for (let i = 1; i < HUE_STOPS.length; i++) {
		const [x1, h1] = HUE_STOPS[i];

		if (v <= x1) {
			const [x0, h0] = HUE_STOPS[i - 1];
			hue = h0 + ((h1 - h0) * (v - x0)) / (x1 - x0);
			break;
		}
	}

	return `hsl(${Math.round(hue)} 72% 44%)`;
}

export function StatBar({
	stat,
	value,
	preview,
	highlight = false,
	compact = false,
	suffix,
}: {
	stat: StatOption;
	value: number | null | undefined;
	/** Projected value to show alongside the current one, e.g. while choosing a pick. */
	preview?: number | null;
	highlight?: boolean;
	compact?: boolean;
	suffix?: ReactNode;
}) {
	const shown = preview ?? value;
	const delta =
		preview !== undefined && preview !== null ? preview - (value ?? 0) : 0;

	return (
		<div
			className={cn(
				'grid items-center gap-3',
				compact
					? 'grid-cols-[minmax(0,7.5rem)_1fr_2.25rem] gap-2 text-xs'
					: 'grid-cols-[minmax(0,8.5rem)_1fr_4.5rem] text-sm',
				highlight && 'rounded-md bg-accent px-1.5 py-0.5 font-semibold',
			)}
			title={stat.description}
		>
			<span className="flex min-w-0 items-center gap-1.5 truncate">
				<NamedIcon
					name={stat.icon}
					className="size-3.5 shrink-0 opacity-70"
				/>
				{stat.label}
				{suffix}
			</span>
			<span
				className={cn(
					'overflow-hidden rounded-full bg-muted-foreground/15',
					compact ? 'h-2' : 'h-3',
				)}
			>
				<span
					className={cn(
						'block h-full rounded-full transition-all duration-500',
						delta !== 0 && 'opacity-80',
					)}
					style={{
						width: `${shown ?? 0}%`,
						backgroundColor: statColour(shown),
					}}
				/>
			</span>
			<span className="text-right font-semibold tabular-nums">
				{shown ?? '–'}
				{delta !== 0 && (
					<span
						className={cn(
							'ml-1 text-xs font-medium',
							delta > 0
								? 'text-green-600 dark:text-green-400'
								: 'text-red-600 dark:text-red-400',
						)}
					>
						{delta > 0 ? `+${delta}` : delta}
					</span>
				)}
			</span>
		</div>
	);
}
