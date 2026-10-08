import { cn } from '@/lib/utils';
import type { Conservation } from '@/types/game';

const tones: Record<string, string> = {
	EX: 'bg-iucn-ex text-white',
	EW: 'bg-iucn-ew text-white',
	CR: 'bg-iucn-cr text-white',
	EN: 'bg-iucn-en text-black',
	VU: 'bg-iucn-vu text-black',
	NT: 'bg-iucn-nt text-black',
	LC: 'bg-iucn-lc text-black',
	DD: 'bg-iucn-dd text-black',
	NE: 'bg-iucn-dd text-black',
};

/**
 * IUCN Red List badge using the official category colours.
 */
export function ConservationBadge({
	conservation,
	showLabel = true,
	className,
}: {
	conservation: Conservation | null;
	showLabel?: boolean;
	className?: string;
}) {
	if (!conservation) {
		return null;
	}

	return (
		<span
			className={cn(
				'inline-flex items-center gap-1.5 rounded-full border border-black/10 py-0.5 pr-2.5 pl-0.5 text-xs font-semibold',
				'bg-card',
				className,
			)}
			title={`IUCN Red List: ${conservation.label}${conservation.trend ? ` (population ${conservation.trend.toLowerCase()})` : ''}`}
		>
			<span
				className={cn(
					'inline-flex size-6 items-center justify-center rounded-full text-[0.65rem] font-bold',
					tones[conservation.code] ?? tones.NE,
				)}
			>
				{conservation.code}
			</span>
			{showLabel && conservation.label}
		</span>
	);
}

/**
 * Horizontal Red List scale with the animal's category highlighted.
 */
export function ConservationScale({
	current,
	statuses,
}: {
	current: string | null;
	statuses: { code: string; label: string }[];
}) {
	return (
		<div className="flex flex-wrap gap-1">
			{statuses.map((s) => (
				<span
					key={s.code}
					title={s.label}
					className={cn(
						'inline-flex size-9 items-center justify-center rounded-full text-xs font-bold transition',
						tones[s.code],
						current === s.code
							? 'scale-110 ring-2 ring-foreground ring-offset-2 ring-offset-background'
							: 'opacity-35',
					)}
				>
					{s.code}
				</span>
			))}
		</div>
	);
}
