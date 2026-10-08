import { Link } from '@inertiajs/react';
import { Lightbulb, MapPin, PawPrint } from 'lucide-react';
import type { CSSProperties } from 'react';
import { ConservationBadge } from '@/components/game/conservation-badge';
import { StatBar } from '@/components/game/stat-bar';
import { cn } from '@/lib/utils';
import animals from '@/routes/animals';
import type {
	AnimalArt as AnimalArtType,
	AnimalCard as AnimalCardType,
	StatKey,
	StatOption,
} from '@/types/game';

export function AnimalImage({
	animal,
	className,
}: {
	animal: Pick<
		AnimalCardType,
		'name' | 'image_url' | 'image_credit' | 'image_licence'
	>;
	className?: string;
}) {
	return (
		<div
			className={cn(
				'relative overflow-hidden bg-gradient-to-br from-wild-moss/30 to-wild-amber/30',
				className,
			)}
		>
			{animal.image_url ? (
				<img
					src={animal.image_url}
					alt={animal.name}
					loading="lazy"
					className="h-full w-full object-cover"
				/>
			) : (
				<div className="flex h-full w-full items-center justify-center font-heading text-4xl font-bold text-foreground/30">
					{animal.name.charAt(0)}
				</div>
			)}
			{animal.image_credit && (
				<span className="absolute right-1 bottom-1 max-w-[90%] truncate rounded bg-black/55 px-1.5 py-0.5 text-[0.6rem] text-white/90">
					{animal.image_credit}
					{animal.image_licence ? ` · ${animal.image_licence}` : ''}
				</span>
			)}
		</div>
	);
}

const silhouetteMaskStyle = (url: string): CSSProperties => ({
	maskImage: `url("${url}")`,
	WebkitMaskImage: `url("${url}")`,
	maskSize: 'contain',
	WebkitMaskSize: 'contain',
	maskRepeat: 'no-repeat',
	WebkitMaskRepeat: 'no-repeat',
	maskPosition: 'center',
	WebkitMaskPosition: 'center',
});

/**
 * Single tinted silhouette (PhyloPic mask). Used by cards and hybrid art.
 */
export function SilhouetteFigure({
	name,
	art,
	className,
	artClassName,
}: {
	name: string;
	art: AnimalArtType | null;
	className?: string;
	artClassName?: string;
}) {
	if (!art) {
		return (
			<PawPrint
				aria-label={name}
				className={cn('text-foreground/25', className, artClassName)}
			/>
		);
	}

	return (
		<span
			role="img"
			aria-label={`${name} silhouette`}
			title={
				art.taxon
					? `Silhouette of a close relative (${art.taxon})`
					: name
			}
			className={cn('block bg-foreground/80', className, artClassName)}
			style={silhouetteMaskStyle(art.url)}
		/>
	);
}

/**
 * Tinted licence-free silhouette, so every animal shares one illustration style. Falls back to a
 * paw print when no art has been imported.
 */
export function AnimalArt({
	animal,
	className,
	artClassName,
	showCredit = false,
}: {
	animal: { name: string; art: AnimalArtType | null };
	className?: string;
	artClassName?: string;
	showCredit?: boolean;
}) {
	const art = animal.art;

	return (
		<div
			className={cn(
				'relative flex items-center justify-center overflow-hidden',
				className,
			)}
		>
			<SilhouetteFigure
				name={animal.name}
				art={art}
				className="h-[80%] w-[80%]"
				artClassName={artClassName}
			/>
			{showCredit && art && (
				<a
					href={art.source_url ?? undefined}
					target="_blank"
					rel="noreferrer"
					onClick={(e) => e.stopPropagation()}
					className="absolute right-1 bottom-1 max-w-[90%] truncate rounded bg-black/55 px-1.5 py-0.5 text-[0.6rem] text-white/90"
				>
					Art: {art.credit ?? 'PhyloPic'}
					{art.licence ? ` · ${art.licence}` : ''}
					{art.taxon ? ` · shows ${art.taxon}` : ''}
				</a>
			)}
		</div>
	);
}

/**
 * Trading-card style animal with an educational fact, conservation status and stat bars.
 */
export function AnimalCard({
	animal,
	stats,
	selected = false,
	highlightStats = [],
	onSelect,
	showStats = true,
	showLink = true,
	className,
	style,
}: {
	animal: AnimalCardType;
	stats: StatOption[];
	selected?: boolean;
	highlightStats?: StatKey[];
	onSelect?: () => void;
	showStats?: boolean;
	showLink?: boolean;
	className?: string;
	style?: CSSProperties;
}) {
	const interactive = onSelect !== undefined;

	return (
		<article
			style={style}
			className={cn(
				'flex flex-col overflow-hidden rounded-2xl border bg-card shadow-lg backdrop-blur transition',
				interactive &&
					'cursor-pointer hover:-translate-y-1 hover:shadow-xl',
				selected && 'ring-4 ring-primary',
				className,
			)}
			onClick={onSelect}
			role={interactive ? 'button' : undefined}
			tabIndex={interactive ? 0 : undefined}
			aria-pressed={interactive ? selected : undefined}
			onKeyDown={(e) => {
				if (interactive && (e.key === 'Enter' || e.key === ' ')) {
					e.preventDefault();
					onSelect?.();
				}
			}}
		>
			<AnimalArt
				animal={animal}
				showCredit
				className="aspect-[4/3] bg-gradient-to-br from-wild-moss/25 to-wild-amber/25"
			/>
			<div className="flex flex-1 flex-col gap-3 p-4">
				<header className="space-y-1">
					<div className="flex items-start justify-between gap-2">
						<h3 className="text-lg leading-tight font-semibold">
							{animal.name}
						</h3>
						<ConservationBadge
							conservation={animal.conservation}
							showLabel={false}
						/>
					</div>
					{animal.scientific_name && (
						<p className="text-xs text-muted-foreground italic">
							{animal.scientific_name}
						</p>
					)}
					<div className="flex flex-wrap gap-1 text-[0.7rem] text-muted-foreground">
						{[animal.taxon_class, animal.diet, animal.domain]
							.filter(Boolean)
							.map((tag) => (
								<span
									key={tag}
									className="rounded-full bg-secondary px-2 py-0.5 capitalize"
								>
									{tag}
								</span>
							))}
					</div>
				</header>

				{animal.fact && (
					<p className="flex gap-2 rounded-lg bg-accent p-2.5 text-xs leading-relaxed">
						<Lightbulb className="mt-0.5 size-3.5 shrink-0 text-primary" />
						<span>{animal.fact.body}</span>
					</p>
				)}

				{animal.habitat && (
					<p className="flex gap-2 text-xs leading-relaxed text-muted-foreground">
						<MapPin className="mt-0.5 size-3.5 shrink-0" />
						<span className="line-clamp-2">{animal.habitat}</span>
					</p>
				)}

				{showStats && (
					<div className="mt-auto space-y-1 pt-1">
						{stats.map((stat) => (
							<StatBar
								key={stat.value}
								stat={stat}
								value={animal.stats[stat.value]}
								highlight={highlightStats.includes(stat.value)}
								compact
							/>
						))}
					</div>
				)}

				{showLink && (
					<Link
						href={animals.show(animal.slug)}
						className="text-xs font-medium text-primary underline-offset-4 hover:underline"
						onClick={(e) => e.stopPropagation()}
					>
						Learn more about the {animal.name.toLowerCase()} →
					</Link>
				)}
			</div>
		</article>
	);
}
