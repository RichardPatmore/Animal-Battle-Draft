import { AnimalArt } from '@/components/game/animal-card';
import { StatBar } from '@/components/game/stat-bar';
import { NamedIcon } from '@/components/game/stat-icon';
import { cn } from '@/lib/utils';
import type {
	AnimalArt as AnimalArtType,
	DraftPick,
	StatKey,
	StatOption,
} from '@/types/game';

/**
 * One box per stat showing the art of the animal drafted into that slot. While choosing, open
 * slots can be clicked and the selected animal is previewed as a ghost in the chosen slot.
 */
export function StatBoxes({
	stats,
	picks,
	selectedStat,
	openStats = [],
	preview,
	onSelectStat,
}: {
	stats: StatOption[];
	picks: DraftPick[];
	selectedStat?: StatKey | null;
	openStats?: StatKey[];
	preview?: { name: string; art: AnimalArtType | null } | null;
	onSelectStat?: (stat: StatKey) => void;
}) {
	const pickByStat = Object.fromEntries(
		picks.map((p) => [p.stat, p]),
	) as Partial<Record<StatKey, DraftPick>>;

	return (
		<div className="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-7">
			{stats.map((stat) => {
				const pick = pickByStat[stat.value];
				const isOpen = openStats.includes(stat.value);
				const clickable = Boolean(onSelectStat && isOpen && preview);
				const selected = selectedStat === stat.value;
				const ghost = selected && !pick ? preview : null;

				return (
					<button
						key={stat.value}
						type="button"
						disabled={!clickable}
						onClick={() => onSelectStat?.(stat.value)}
						title={stat.description}
						className={cn(
							'flex flex-col overflow-hidden rounded-xl border bg-card text-center backdrop-blur transition',
							clickable &&
								'cursor-pointer hover:-translate-y-0.5 hover:border-primary',
							!clickable && 'cursor-default',
							selected && 'border-primary ring-2 ring-primary',
						)}
					>
						<span className="flex items-center justify-center gap-1 border-b px-1 py-1.5 text-[0.7rem] font-semibold tracking-wide uppercase">
							<NamedIcon
								name={stat.icon}
								className="size-3.5 shrink-0 text-primary"
							/>
							<span className="truncate">{stat.label}</span>
						</span>
						{pick ? (
							<AnimalArt
								animal={pick.animal}
								className="aspect-square bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
							/>
						) : ghost ? (
							<AnimalArt
								animal={ghost}
								className="aspect-square bg-accent"
								artClassName="opacity-40"
							/>
						) : (
							<span
								className={cn(
									'm-2 flex aspect-square items-center justify-center rounded-lg border-2 border-dashed text-2xl text-muted-foreground/40',
									clickable &&
										'border-primary/40 text-primary/50',
								)}
							>
								?
							</span>
						)}
						<span className="truncate px-1 py-1.5 text-[0.7rem] text-muted-foreground">
							{pick?.animal.name ??
								(ghost ? ghost.name : 'Empty')}
						</span>
					</button>
				);
			})}
		</div>
	);
}

/**
 * Centred column of the hybrid's blended stat bars: name left, bar middle, value right.
 */
export function HybridBars({
	stats,
	hybrid,
	preview,
	highlightStat,
	className,
}: {
	stats: StatOption[];
	hybrid: Record<StatKey, number | null>;
	preview?: Record<StatKey, number | null> | null;
	highlightStat?: StatKey | null;
	className?: string;
}) {
	return (
		<div className={cn('mx-auto w-full max-w-xl space-y-1.5', className)}>
			{stats.map((stat) => (
				<StatBar
					key={stat.value}
					stat={stat}
					value={hybrid[stat.value]}
					preview={preview ? preview[stat.value] : undefined}
					highlight={highlightStat === stat.value}
				/>
			))}
		</div>
	);
}
