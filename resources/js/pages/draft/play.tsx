import { Head, router } from '@inertiajs/react';
import { Check, Info } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AnimalCard } from '@/components/game/animal-card';
import { HybridBars, StatBoxes } from '@/components/game/hybrid-board';
import { statColour } from '@/components/game/stat-bar';
import { NamedIcon } from '@/components/game/stat-icon';
import { Button } from '@/components/ui/button';
import { blendHybrid } from '@/lib/hybrid';
import { cn } from '@/lib/utils';
import drafts from '@/routes/drafts';
import type {
	AnimalCard as AnimalCardType,
	DraftPick,
	StatKey,
	StatOption,
} from '@/types/game';

type PlayDraft = {
	id: string;
	round: number;
	total_rounds: number;
	category: string;
	size_relative: boolean;
	hybrid: Record<StatKey, number | null>;
	open_stats: StatKey[];
	picks: DraftPick[];
	own_weight: number;
};

export default function DraftPlay({
	draft,
	offer,
	stats,
}: {
	draft: PlayDraft;
	offer: AnimalCardType[];
	stats: StatOption[];
}) {
	const [selectedId, setSelectedId] = useState<number | null>(null);
	const [selectedStat, setSelectedStat] = useState<StatKey | null>(null);
	const [submitting, setSubmitting] = useState(false);

	const selected = offer.find((a) => a.id === selectedId) ?? null;
	const openStats = stats.filter((s) => draft.open_stats.includes(s.value));
	const total = Object.values(draft.hybrid).reduce<number>(
		(sum, v) => sum + (v ?? 0),
		0,
	);

	const projected = useMemo(() => {
		if (!selected || !selectedStat) {
			return null;
		}

		return blendHybrid(
			[
				...draft.picks.map((p) => ({ stat: p.stat, stats: p.stats })),
				{ stat: selectedStat, stats: selected.stats },
			],
			stats.map((s) => s.value),
			draft.own_weight,
		);
	}, [selected, selectedStat, draft.picks, draft.own_weight, stats]);

	const projectedTotal = projected
		? Object.values(projected).reduce<number>((sum, v) => sum + (v ?? 0), 0)
		: null;

	const selectAnimal = (id: number) => {
		setSelectedId(id);

		if (selectedStat && !draft.open_stats.includes(selectedStat)) {
			setSelectedStat(null);
		}
	};

	const confirm = () => {
		if (!selected || !selectedStat) {
			return;
		}

		setSubmitting(true);
		router.post(
			drafts.pick(draft.id).url,
			{ animal_id: selected.id, stat: selectedStat },
			{
				preserveScroll: false,
				onFinish: () => {
					setSubmitting(false);
					setSelectedId(null);
					setSelectedStat(null);
				},
			},
		);
	};

	return (
		<>
			<Head title={`Round ${draft.round} – Draft`} />

			<div className="flex flex-wrap items-end justify-between gap-4 pb-6">
				<div>
					<p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
						{draft.category}
					</p>
					<h1 className="text-3xl font-bold">
						Round {draft.round}{' '}
						<span className="text-muted-foreground">
							/ {draft.total_rounds}
						</span>
					</h1>
				</div>
				<div className="flex gap-1" aria-label="Draft progress">
					{Array.from({ length: draft.total_rounds }).map((_, i) => (
						<span
							key={i}
							className={cn(
								'h-2 w-6 rounded-full',
								i < draft.round - 1
									? 'bg-primary'
									: i === draft.round - 1
										? 'bg-primary/50'
										: 'bg-muted-foreground/20',
							)}
						/>
					))}
				</div>
			</div>

			{draft.size_relative && (
				<p className="mb-4 flex items-center gap-2 rounded-xl border bg-card px-4 py-2 text-sm text-muted-foreground">
					<Info className="size-4 text-primary" />
					Size-relative pool: stats are scaled against the other
					animals in this category, not the whole animal kingdom.
				</p>
			)}

			<section aria-label="Your hybrid" className="space-y-6">
				<StatBoxes
					stats={stats}
					picks={draft.picks}
					selectedStat={selectedStat}
					openStats={draft.open_stats}
					preview={selected}
					onSelectStat={setSelectedStat}
				/>

				<div className="rounded-2xl border bg-card p-5 backdrop-blur">
					<HybridBars
						stats={stats}
						hybrid={draft.hybrid}
						preview={projected}
						highlightStat={selectedStat}
					/>
					<p className="mx-auto mt-4 max-w-xl border-t pt-3 text-center text-sm text-muted-foreground">
						Total{' '}
						<span className="font-heading font-bold text-primary tabular-nums">
							{projectedTotal ?? total}
						</span>
						{projectedTotal !== null &&
							projectedTotal !== total && (
								<span className="ml-1 tabular-nums">
									(was {total})
								</span>
							)}
						<span className="mt-1 block text-xs">
							Each stat is {Math.round(draft.own_weight * 100)}%
							the animal you picked for it, and{' '}
							{Math.round((1 - draft.own_weight) * 100)}% shared
							between the rest of your team.
						</span>
					</p>
				</div>
			</section>

			<section className="mt-10">
				<h2 className="mb-3 text-lg font-semibold">
					1. Pick an animal
				</h2>
				<div className="grid gap-4 md:grid-cols-3">
					{offer.map((animal, i) => (
						<AnimalCard
							key={animal.id}
							animal={animal}
							stats={stats}
							selected={animal.id === selectedId}
							highlightStats={selectedStat ? [selectedStat] : []}
							onSelect={() => selectAnimal(animal.id)}
							className="animate-card-in"
							style={{ animationDelay: `${i * 90}ms` }}
						/>
					))}
				</div>
			</section>

			<section
				className={cn(
					'mt-6 rounded-2xl border bg-card p-5 backdrop-blur transition',
					!selected && 'opacity-60',
				)}
			>
				<h2 className="mb-1 text-lg font-semibold">
					2. Choose the stat it leads
				</h2>
				<p className="mb-4 text-sm text-muted-foreground">
					{selected
						? `Which slot should the ${selected.name.toLowerCase()} fill? You can also click a box at the top.`
						: 'Select an animal above first.'}
				</p>
				<div className="grid gap-2 sm:grid-cols-3">
					{openStats.map((stat) => {
						const value = selected?.stats[stat.value];

						return (
							<button
								key={stat.value}
								type="button"
								disabled={!selected}
								onClick={() => setSelectedStat(stat.value)}
								title={stat.description}
								className={cn(
									'flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 text-left transition',
									'hover:border-primary disabled:pointer-events-none',
									selectedStat === stat.value &&
										'border-primary bg-accent ring-2 ring-primary',
								)}
							>
								<span className="flex items-center gap-2 text-sm font-medium">
									<NamedIcon
										name={stat.icon}
										className="size-4 text-primary"
									/>
									{stat.label}
								</span>
								{value !== undefined && (
									<span
										className="min-w-9 rounded-md px-1.5 py-0.5 text-center text-sm font-bold text-white tabular-nums"
										style={{
											backgroundColor: statColour(value),
										}}
									>
										{value}
									</span>
								)}
							</button>
						);
					})}
				</div>
				<div className="mt-5 flex justify-end">
					<Button
						size="lg"
						disabled={!selected || !selectedStat || submitting}
						onClick={confirm}
					>
						<Check className="size-4" />
						{selected && selectedStat
							? `Take ${selected.name}'s ${stats.find((s) => s.value === selectedStat)?.label}`
							: 'Lock in pick'}
					</Button>
				</div>
			</section>
		</>
	);
}
