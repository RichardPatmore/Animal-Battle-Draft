import { Head, router } from '@inertiajs/react';
import { BookOpen, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AnimalCard } from '@/components/game/animal-card';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import animals from '@/routes/animals';
import type { AnimalCard as AnimalCardType, StatOption } from '@/types/game';

export default function AnimalsIndex({
	animals: list,
	filters,
	categories,
	stats,
}: {
	animals: AnimalCardType[];
	filters: { search: string; category: string };
	categories: { slug: string; name: string }[];
	stats: StatOption[];
}) {
	const [search, setSearch] = useState(filters.search);
	const firstRender = useRef(true);

	const apply = (next: { search?: string; category?: string }) => {
		const query = { ...filters, search, ...next };

		router.get(
			animals.index.url(),
			Object.fromEntries(
				Object.entries(query).filter(([, v]) => v !== ''),
			),
			{ preserveState: true, preserveScroll: true, replace: true },
		);
	};

	useEffect(() => {
		if (firstRender.current) {
			firstRender.current = false;

			return;
		}

		const timer = setTimeout(() => apply({ search }), 300);

		return () => clearTimeout(timer);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [search]);

	return (
		<>
			<Head title="Encyclopedia" />

			<div className="space-y-2 pb-6">
				<h1 className="flex items-center gap-2 text-3xl font-bold">
					<BookOpen className="size-7 text-primary" />
					Animal encyclopedia
				</h1>
				<p className="max-w-2xl text-muted-foreground">
					Every animal in the draft pool, with the real-world
					measurements behind its stats, conservation status and
					habitat.
				</p>
			</div>

			<div className="flex flex-wrap items-center gap-3 pb-6">
				<div className="relative w-full max-w-xs">
					<Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
					<Input
						value={search}
						onChange={(e) => setSearch(e.target.value)}
						placeholder="Search by name…"
						className="pl-9"
					/>
				</div>
				<div className="flex flex-wrap gap-1.5">
					{[{ slug: '', name: 'All' }, ...categories].map((c) => (
						<button
							key={c.slug}
							type="button"
							onClick={() => apply({ category: c.slug })}
							className={cn(
								'rounded-full border px-3 py-1 text-sm transition',
								filters.category === c.slug
									? 'border-primary bg-primary text-primary-foreground'
									: 'bg-card hover:border-primary',
							)}
						>
							{c.name}
						</button>
					))}
				</div>
			</div>

			<p className="pb-3 text-sm text-muted-foreground">
				{list.length} {list.length === 1 ? 'animal' : 'animals'}
			</p>

			{list.length === 0 ? (
				<p className="rounded-2xl border bg-card p-8 text-center text-muted-foreground">
					No animals match that search.
				</p>
			) : (
				<div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					{list.map((animal) => (
						<AnimalCard
							key={animal.id}
							animal={animal}
							stats={stats}
							showStats={false}
						/>
					))}
				</div>
			)}
		</>
	);
}
