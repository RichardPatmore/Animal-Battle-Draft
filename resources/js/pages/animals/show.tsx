import { Head, Link } from '@inertiajs/react';
import {
	ExternalLink,
	Globe2,
	Lightbulb,
	MapPin,
	Ruler,
	TrendingDown,
	TrendingUp,
} from 'lucide-react';
import { AnimalArt, AnimalImage } from '@/components/game/animal-card';
import {
	ConservationBadge,
	ConservationScale,
} from '@/components/game/conservation-badge';
import { StatBar } from '@/components/game/stat-bar';
import { StatRadar } from '@/components/game/stat-radar';
import animals from '@/routes/animals';
import type { AnimalProfile, StatOption } from '@/types/game';

function Panel({
	title,
	children,
}: {
	title: string;
	children: React.ReactNode;
}) {
	return (
		<section className="space-y-3 rounded-2xl border bg-card p-5 backdrop-blur">
			<h2 className="text-lg font-semibold">{title}</h2>
			{children}
		</section>
	);
}

export default function AnimalShow({
	animal,
	stats,
	statuses,
}: {
	animal: AnimalProfile;
	stats: StatOption[];
	statuses: { code: string; label: string }[];
}) {
	const physical = animal.measurements.filter((m) => !m.is_score);
	const scores = animal.measurements.filter((m) => m.is_score);
	const trend = animal.population_trend?.toLowerCase();

	return (
		<>
			<Head title={animal.name} />

			<Link
				href={animals.index()}
				className="text-sm text-muted-foreground hover:underline"
			>
				← Encyclopedia
			</Link>

			<div className="grid gap-6 pt-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
				<div className="space-y-3">
					<AnimalImage
						animal={animal}
						className="aspect-[4/3] rounded-2xl border shadow-lg"
					/>
					{animal.art && (
						<AnimalArt
							animal={animal}
							showCredit
							className="h-36 rounded-2xl border bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
						/>
					)}
				</div>

				<div className="space-y-4">
					<div className="space-y-1">
						<h1 className="text-4xl font-bold">{animal.name}</h1>
						{animal.scientific_name && (
							<p className="text-lg text-muted-foreground italic">
								{animal.scientific_name}
							</p>
						)}
						<p className="text-sm text-muted-foreground">
							{[
								animal.taxon_class,
								animal.taxon_order,
								animal.taxon_family,
							]
								.filter(Boolean)
								.join(' › ')}
						</p>
					</div>

					<div className="flex flex-wrap gap-1.5 text-xs">
						{[animal.diet, animal.domain]
							.filter(Boolean)
							.map((tag) => (
								<span
									key={tag}
									className="rounded-full bg-secondary px-2.5 py-1 capitalize"
								>
									{tag}
								</span>
							))}
						{animal.categories.map((c) => (
							<Link
								key={c.slug}
								href={animals.index({
									query: { category: c.slug },
								})}
								className="rounded-full border px-2.5 py-1 hover:border-primary"
							>
								{c.name}
							</Link>
						))}
					</div>

					{animal.description && (
						<p className="leading-relaxed">{animal.description}</p>
					)}

					{animal.wikipedia_url && (
						<a
							href={animal.wikipedia_url}
							target="_blank"
							rel="noreferrer"
							className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
						>
							Read more on Wikipedia{' '}
							<ExternalLink className="size-3.5" />
						</a>
					)}
				</div>
			</div>

			<div className="grid gap-6 pt-8 lg:grid-cols-2">
				<Panel title="Conservation status">
					<div className="flex flex-wrap items-center gap-3">
						<ConservationBadge conservation={animal.conservation} />
						{trend && (
							<span className="inline-flex items-center gap-1 text-sm text-muted-foreground capitalize">
								{trend === 'decreasing' ? (
									<TrendingDown className="size-4 text-wild-rust" />
								) : (
									<TrendingUp className="size-4" />
								)}
								Population {trend}
							</span>
						)}
					</div>
					<ConservationScale
						current={animal.conservation?.code ?? null}
						statuses={statuses}
					/>
					{!animal.conservation && (
						<p className="text-sm text-muted-foreground">
							This animal has not been assessed yet.
						</p>
					)}
				</Panel>

				<Panel title="Where it lives">
					{animal.habitat && (
						<p className="flex gap-2 text-sm leading-relaxed">
							<MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
							{animal.habitat}
						</p>
					)}
					{animal.regions.length > 0 && (
						<p className="flex flex-wrap items-center gap-1.5 text-sm">
							<Globe2 className="size-4 text-primary" />
							{animal.regions.map((r) => (
								<span
									key={r}
									className="rounded-full bg-secondary px-2.5 py-0.5"
								>
									{r}
								</span>
							))}
						</p>
					)}
					{animal.habitats.length > 0 && (
						<p className="text-sm text-muted-foreground">
							{animal.habitats.join(', ')}
						</p>
					)}
				</Panel>

				<Panel title="Battle stats">
					<div className="grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_13rem]">
						<div className="space-y-1.5">
							{stats.map((stat) => (
								<StatBar
									key={stat.value}
									stat={stat}
									value={animal.stats[stat.value]}
								/>
							))}
						</div>
						<StatRadar
							stats={stats}
							size={240}
							series={[
								{
									label: animal.name,
									values: animal.stats,
									className:
										'fill-wild-amber/30 stroke-wild-amber',
								},
							]}
						/>
					</div>
					<p className="text-xs text-muted-foreground">
						Stats are derived from the measurements below and ranked
						against every other animal in the pool.
					</p>
				</Panel>

				<Panel title="Did you know?">
					{animal.facts.length === 0 ? (
						<p className="text-sm text-muted-foreground">
							No facts yet.
						</p>
					) : (
						<ul className="space-y-2.5">
							{animal.facts.map((fact, i) => (
								<li
									key={i}
									className="flex gap-2 rounded-lg bg-accent p-3 text-sm leading-relaxed"
								>
									<Lightbulb className="mt-0.5 size-4 shrink-0 text-primary" />
									<span>
										<span className="font-semibold">
											{fact.type}:{' '}
										</span>
										{fact.body}
									</span>
								</li>
							))}
						</ul>
					)}
				</Panel>
			</div>

			<div className="pt-6">
				<Panel title="The data behind the stats">
					{animal.measurements.length === 0 ? (
						<p className="text-sm text-muted-foreground">
							No measurements recorded yet.
						</p>
					) : (
						<div className="overflow-x-auto">
							<table className="w-full text-sm">
								<thead className="text-left text-xs text-muted-foreground uppercase">
									<tr>
										<th className="py-2 pr-4">
											Measurement
										</th>
										<th className="py-2 pr-4">Value</th>
										<th className="py-2 pr-4">
											Confidence
										</th>
										<th className="py-2">Source</th>
									</tr>
								</thead>
								<tbody>
									{[...physical, ...scores].map((m) => (
										<tr key={m.key} className="border-t">
											<td className="py-2 pr-4">
												<span className="inline-flex items-center gap-1.5">
													{!m.is_score && (
														<Ruler className="size-3.5 text-muted-foreground" />
													)}
													{m.label}
												</span>
											</td>
											<td className="py-2 pr-4 font-medium tabular-nums">
												{m.value}
											</td>
											<td className="py-2 pr-4">
												{m.confidence}
											</td>
											<td className="py-2 text-muted-foreground">
												{m.source_url ? (
													<a
														href={m.source_url}
														target="_blank"
														rel="noreferrer"
														className="hover:underline"
													>
														{m.source}
													</a>
												) : (
													(m.source ?? '—')
												)}
											</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}
				</Panel>
			</div>
		</>
	);
}
