import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Dices, Ruler, Sparkles, Swords } from 'lucide-react';
import { useState } from 'react';
import { AnimalCard } from '@/components/game/animal-card';
import { NamedIcon } from '@/components/game/stat-icon';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import drafts from '@/routes/drafts';
import type { AnimalCard as AnimalCardType, StatOption } from '@/types/game';

type CategoryOption = {
	slug: string;
	name: string;
	description: string | null;
	icon: string | null;
	animals_count: number;
	size_relative: boolean;
	playable: boolean;
};

type RecentDraft = {
	id: string;
	status: string;
	hybrid_name: string | null;
	final_score: number | null;
	category: string;
	created_at: string | null;
};

export default function Home({
	categories,
	recentDrafts,
	featured,
	stats,
}: {
	categories: CategoryOption[];
	recentDrafts: RecentDraft[];
	featured: AnimalCardType | null;
	stats: StatOption[];
}) {
	const [starting, setStarting] = useState<string | null>(null);

	const start = (slug: string) => {
		setStarting(slug);
		router.post(
			drafts.store().url,
			{ category: slug },
			{ onFinish: () => setStarting(null) },
		);
	};

	return (
		<>
			<Head title="Draft your ultimate animal" />

			<section className="grid gap-8 pt-4 pb-10 lg:grid-cols-[1.4fr_1fr] lg:items-center">
				<div className="space-y-5">
					<p className="inline-flex items-center gap-2 rounded-full bg-accent px-3 py-1 text-xs font-semibold tracking-wide text-primary uppercase">
						<Sparkles className="size-3.5" /> Real animals, real
						data
					</p>
					<h1 className="text-4xl leading-tight font-bold tracking-tight sm:text-5xl">
						Build the ultimate animal,
						<br />
						<span className="text-primary">
							one stat at a time.
						</span>
					</h1>
					<p className="max-w-xl text-lg text-muted-foreground">
						Each round you're offered three real animals. Pick one
						and choose which of its stats your hybrid inherits: a
						cheetah's speed, a crocodile's bite, an octopus's brain.
						Fill all {stats.length} stats, then send your creation
						into the arena against real animals.
					</p>
					<ol className="grid gap-3 text-sm sm:grid-cols-3">
						{[
							{
								icon: Dices,
								title: 'Draft',
								text: 'Pick 1 of 3 animals each round.',
							},
							{
								icon: Ruler,
								title: 'Assign',
								text: 'Slot it into an open stat.',
							},
							{
								icon: Swords,
								title: 'Brawl',
								text: 'Test your hybrid against real animals.',
							},
						].map((step) => (
							<li
								key={step.title}
								className="rounded-xl border bg-card p-3 backdrop-blur"
							>
								<step.icon className="mb-1.5 size-5 text-primary" />
								<p className="font-semibold">{step.title}</p>
								<p className="text-muted-foreground">
									{step.text}
								</p>
							</li>
						))}
					</ol>
				</div>

				{featured && (
					<div className="space-y-2">
						<p className="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
							Animal of the day
						</p>
						<AnimalCard
							animal={featured}
							stats={stats}
							showStats={false}
							className="animate-card-in"
						/>
					</div>
				)}
			</section>

			<section className="space-y-4">
				<div className="flex items-end justify-between gap-4">
					<div>
						<h2 className="text-2xl font-bold">
							Choose your draft pool
						</h2>
						<p className="text-muted-foreground">
							Every animal in the pool could be offered to you.
						</p>
					</div>
				</div>

				<div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
					{categories.map((category) => (
						<button
							key={category.slug}
							type="button"
							disabled={!category.playable || starting !== null}
							onClick={() => start(category.slug)}
							className={cn(
								'group flex flex-col gap-3 rounded-2xl border bg-card p-5 text-left shadow-sm backdrop-blur transition',
								'hover:-translate-y-0.5 hover:border-primary hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50',
							)}
						>
							<div className="flex items-center justify-between">
								<span className="flex size-11 items-center justify-center rounded-xl bg-accent text-primary">
									<NamedIcon
										name={category.icon}
										className="size-5"
									/>
								</span>
								<span className="text-xs text-muted-foreground">
									{category.animals_count} animals
								</span>
							</div>
							<div>
								<h3 className="text-lg font-semibold">
									{category.name}
								</h3>
								<p className="text-sm text-muted-foreground">
									{category.description}
								</p>
							</div>
							<span className="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-primary">
								{starting === category.slug
									? 'Shuffling the deck…'
									: category.playable
										? 'Start draft'
										: 'Needs more animals'}
								<ArrowRight className="size-4 transition group-hover:translate-x-1" />
							</span>
						</button>
					))}
				</div>
			</section>

			{recentDrafts.length > 0 && (
				<section className="mt-12 space-y-3">
					<h2 className="text-xl font-bold">Your recent hybrids</h2>
					<ul className="divide-y rounded-2xl border bg-card backdrop-blur">
						{recentDrafts.map((d) => (
							<li key={d.id}>
								<Link
									href={drafts.show(d.id)}
									className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
								>
									<span>
										<span className="font-semibold">
											{d.hybrid_name ??
												'Draft in progress'}
										</span>
										<span className="block text-xs text-muted-foreground">
											{d.category} · {d.created_at}
										</span>
									</span>
									{d.final_score !== null ? (
										<span className="font-heading text-lg font-bold text-primary tabular-nums">
											{d.final_score}
										</span>
									) : (
										<Button size="sm" variant="secondary">
											Resume
										</Button>
									)}
								</Link>
							</li>
						))}
					</ul>
				</section>
			)}
		</>
	);
}
