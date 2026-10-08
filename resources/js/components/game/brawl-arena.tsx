import { AnimalArt } from '@/components/game/animal-card';
import {
	HybridCreatureArt,
	type HybridContributor,
} from '@/components/game/hybrid-creature-art';
import { cn } from '@/lib/utils';
import type { AnimalArt as AnimalArtType } from '@/types/game';

export type GauntletOpponent = {
	name: string;
	slug: string;
	art: AnimalArtType | null;
	level: number;
	rank_total: number;
	combat_max_hp: number;
};

export type FightFrame = {
	phase: string;
	hybrid_hp: number;
	opponent_hp: number;
	text?: string | null;
};

function hpBarClass(share: number): string {
	if (share <= 0.25) {
		return 'bg-red-500';
	}

	if (share <= 0.5) {
		return 'bg-amber-500';
	}

	return 'bg-green-500';
}

export function BrawlArena({
	hybridName,
	hybridContributors,
	opponent,
	hybridMax,
	opponentMax,
	hybridHp,
	opponentHp,
	battleText,
	animating,
}: {
	hybridName: string;
	hybridContributors: HybridContributor[];
	opponent: GauntletOpponent | null;
	hybridMax: number;
	opponentMax: number;
	hybridHp: number;
	opponentHp: number;
	battleText: string | null;
	animating: boolean;
}) {
	return (
		<div className="mx-auto w-full max-w-3xl space-y-3">
			<div
				className={cn(
					'relative flex items-start justify-center gap-4 rounded-2xl border bg-card p-4 backdrop-blur md:gap-6 md:p-6',
					animating && 'animate-pulse',
				)}
			>
				<FighterCard
					name={hybridName}
					hybridContributors={hybridContributors}
					hp={hybridHp}
					max={hybridMax}
				/>
				<div
					className="flex h-28 w-14 shrink-0 items-center justify-center self-center md:w-16"
					aria-hidden
				>
					<span className="text-2xl font-black text-primary">VS</span>
				</div>
				<FighterCard
					name={opponent?.name ?? '???'}
					art={opponent?.art ?? null}
					hp={opponentHp}
					max={opponentMax}
				/>
			</div>
			<div
				className="mx-auto flex h-16 w-full max-w-lg shrink-0 items-center justify-center overflow-hidden px-4 text-center text-xs leading-snug text-muted-foreground"
				aria-live="polite"
			>
				{battleText ? (
					<p className="line-clamp-3">{battleText}</p>
				) : (
					<span className="opacity-40">Ready when you are.</span>
				)}
			</div>
		</div>
	);
}

function FighterCard({
	name,
	art,
	hybridContributors,
	hp,
	max,
}: {
	name: string;
	art?: AnimalArtType | null;
	hybridContributors?: HybridContributor[];
	hp: number;
	max: number;
}) {
	const share = max > 0 ? hp / max : 0;

	return (
		<div className="flex min-w-0 flex-1 flex-col gap-2">
			{hybridContributors ? (
				<HybridCreatureArt
					name={name}
					contributors={hybridContributors}
					className="aspect-square rounded-xl bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
				/>
			) : (
				<AnimalArt
					animal={{ name, art: art ?? null }}
					className="aspect-square rounded-xl bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
				/>
			)}
			<p className="truncate text-center text-sm font-semibold">{name}</p>
			<div className="h-3 overflow-hidden rounded-full bg-muted-foreground/20">
				<div
					className={cn(
						'h-full rounded-full transition-all duration-300',
						hpBarClass(share),
					)}
					style={{
						width: `${Math.max(0, Math.min(100, share * 100))}%`,
					}}
				/>
			</div>
			<p className="text-center text-xs text-muted-foreground tabular-nums">
				{Math.round(hp)} / {Math.round(max)} HP
			</p>
		</div>
	);
}
