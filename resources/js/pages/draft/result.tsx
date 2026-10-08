import { Head, Link, router } from '@inertiajs/react';
import { Copy, Dna, RotateCcw, Sparkles, Swords, Trophy } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { CreatureNaming } from '@/components/game/creature-naming';
import { AnimalArt } from '@/components/game/animal-card';
import { HybridCreatureArt } from '@/components/game/hybrid-creature-art';
import { ConservationBadge } from '@/components/game/conservation-badge';
import { StatBoxes } from '@/components/game/hybrid-board';
import { StatBar } from '@/components/game/stat-bar';
import { StatRadar } from '@/components/game/stat-radar';
import { NamedIcon } from '@/components/game/stat-icon';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import animals from '@/routes/animals';
import brawl from '@/routes/brawl';
import drafts from '@/routes/drafts';
import type {
	AnimalCard as AnimalCardType,
	DraftPick,
	StatKey,
	StatOption,
	Synergy,
} from '@/types/game';

type ResultDraft = {
	id: string;
	hybrid_name: string | null;
	final_score: number | null;
	max_score: number;
	category: string;
	hybrid: Record<StatKey, number | null>;
	synergies: Synergy[];
	picks: DraftPick[];
	is_owner: boolean;
	completed_at: string | null;
	default_name: string;
	show_name_prompt: boolean;
};

export default function DraftResult({
	draft,
	dna,
	stats,
	name_suggestions,
	allSynergies,
}: {
	draft: ResultDraft;
	dna: AnimalCardType[];
	stats: StatOption[];
	name_suggestions: string[];
	allSynergies: Synergy[];
}) {
	const [namingDismissed, setNamingDismissed] = useState(false);
	const showNaming = draft.show_name_prompt && !namingDismissed;

	const earned = new Set(draft.synergies.map((s) => s.key));
	const statLabel = (key: StatKey) => stats.find((s) => s.value === key);

	const share = async () => {
		await navigator.clipboard.writeText(window.location.href);
		toast.success('Link copied. Challenge your friends!');
	};

	return (
		<>
			<Head title={draft.hybrid_name ?? draft.default_name} />

			{showNaming && (
				<div className="pt-4 pb-2">
					<CreatureNaming
						draftId={draft.id}
						defaultName={draft.default_name}
						suggestions={name_suggestions}
						onDone={() => setNamingDismissed(true)}
					/>
				</div>
			)}

			<section className="grid gap-8 pt-2 pb-10 lg:grid-cols-[1fr_auto] lg:items-center">
				<div className="space-y-4">
					<p className="inline-flex items-center gap-2 rounded-full bg-accent px-3 py-1 text-xs font-semibold tracking-wide text-primary uppercase">
						<Dna className="size-3.5" /> {draft.category} hybrid
					</p>
					<h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
						{draft.hybrid_name}
					</h1>
					<p className="flex items-baseline gap-2">
						<Trophy className="size-6 self-center text-primary" />
						<span className="font-heading text-5xl font-bold text-primary tabular-nums">
							{draft.final_score}
						</span>
						<span className="text-muted-foreground">
							/ {draft.max_score} points
						</span>
					</p>
					<div className="flex flex-wrap gap-2">
						<Button asChild size="lg">
							<Link href={brawl.index(draft.id)}>
								<Swords className="size-4" /> Enter the gauntlet
							</Link>
						</Button>
						<Button
							size="lg"
							variant="secondary"
							onClick={() => router.post(drafts.store().url, {})}
						>
							<RotateCcw className="size-4" /> New draft
						</Button>
						<Button size="lg" variant="ghost" onClick={share}>
							<Copy className="size-4" /> Share
						</Button>
					</div>
				</div>
				<div className="flex flex-col items-center gap-4 rounded-2xl border bg-card p-4 backdrop-blur">
					<HybridCreatureArt
						name={draft.hybrid_name ?? draft.default_name}
						contributors={draft.picks.map((pick) => ({
							slug: pick.animal.slug,
							name: pick.animal.name,
							art: pick.animal.art,
						}))}
						className="aspect-square w-full max-w-[14rem] rounded-xl bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
					/>
					<StatRadar
						stats={stats}
						series={[
							{
								label: draft.hybrid_name ?? 'Hybrid',
								values: draft.hybrid,
								className: 'fill-primary/30 stroke-primary',
							},
						]}
					/>
				</div>
			</section>

			<div className="pb-10">
				<StatBoxes stats={stats} picks={draft.picks} />
			</div>

			<div className="grid gap-8 lg:grid-cols-[1fr_22rem]">
				<section className="space-y-4">
					<h2 className="flex items-center gap-2 text-2xl font-bold">
						<Dna className="size-6 text-primary" /> Hybrid DNA
					</h2>
					<p className="text-muted-foreground">
						Where each part of your creature came from, and what
						makes the real animal special.
					</p>
					<div className="grid gap-4 sm:grid-cols-2">
						{draft.picks.map((pick) => {
							const animal = dna.find(
								(a) => a.slug === pick.animal.slug,
							);
							const stat = statLabel(pick.stat);

							if (!animal || !stat) {
								return null;
							}

							return (
								<article
									key={pick.stat}
									className="flex gap-3 overflow-hidden rounded-2xl border bg-card p-3 backdrop-blur"
								>
									<AnimalArt
										animal={animal}
										className="size-24 shrink-0 rounded-xl bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
									/>
									<div className="min-w-0 space-y-1.5">
										<p className="flex items-center gap-1.5 text-xs font-semibold text-primary uppercase">
											<NamedIcon
												name={stat.icon}
												className="size-3.5"
											/>
											{stat.label}{' '}
											{draft.hybrid[pick.stat] ??
												pick.value}
											<span className="font-normal text-muted-foreground normal-case">
												(own {pick.value})
											</span>
										</p>
										<Link
											href={animals.show(animal.slug)}
											className="block font-semibold hover:underline"
										>
											{animal.name}
										</Link>
										<ConservationBadge
											conservation={animal.conservation}
										/>
										{animal.fact && (
											<p className="line-clamp-3 text-xs text-muted-foreground">
												{animal.fact.body}
											</p>
										)}
									</div>
								</article>
							);
						})}
					</div>
				</section>

				<aside className="space-y-6">
					<div className="space-y-2 rounded-2xl border bg-card p-5 backdrop-blur">
						<h2 className="text-lg font-semibold">Stat sheet</h2>
						{stats.map((stat) => (
							<StatBar
								key={stat.value}
								stat={stat}
								value={draft.hybrid[stat.value]}
							/>
						))}
					</div>

					<div className="space-y-3 rounded-2xl border bg-card p-5 backdrop-blur">
						<h2 className="flex items-center gap-2 text-lg font-semibold">
							<Sparkles className="size-5 text-primary" />{' '}
							Synergies
						</h2>
						<ul className="space-y-2">
							{allSynergies.map((s) => (
								<li
									key={s.key}
									className={cn(
										'rounded-lg border p-2.5 text-sm',
										earned.has(s.key)
											? 'border-primary bg-accent'
											: 'opacity-50',
									)}
								>
									<p className="flex justify-between font-semibold">
										{s.label}
										<span className="text-primary">
											+{s.bonus}
										</span>
									</p>
									<p className="text-xs text-muted-foreground">
										{s.description}
									</p>
								</li>
							))}
						</ul>
					</div>
				</aside>
			</div>
		</>
	);
}
