import { Dna } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { SilhouetteFigure } from '@/components/game/animal-card';
import { cn } from '@/lib/utils';
import type { HybridContributor } from '@/types/game';

export type { HybridContributor };

const FADE_MS = 2800;

/**
 * Visual for a draft hybrid: crossfade through donor silhouettes with a DNA badge centred on top
 * (respects reduced motion).
 */
export function HybridCreatureArt({
	name,
	contributors,
	className,
}: {
	name: string;
	contributors: HybridContributor[];
	className?: string;
}) {
	const donors = useMemo(
		() =>
			contributors.filter(
				(c, i, list) => list.findIndex((x) => x.slug === c.slug) === i,
			),
		[contributors],
	);

	const withArt = donors.filter((d) => d.art);
	const [fadeIndex, setFadeIndex] = useState(0);
	const [motionOk, setMotionOk] = useState(true);

	useEffect(() => {
		const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
		const sync = () => setMotionOk(!mq.matches);
		sync();
		mq.addEventListener('change', sync);

		return () => mq.removeEventListener('change', sync);
	}, []);

	useEffect(() => {
		if (!motionOk || withArt.length < 2) {
			return;
		}

		const id = window.setInterval(() => {
			setFadeIndex((i) => (i + 1) % withArt.length);
		}, FADE_MS);

		return () => window.clearInterval(id);
	}, [motionOk, withArt.length]);

	const fadeTarget = withArt[fadeIndex] ?? withArt[0] ?? null;

	return (
		<div
			className={cn(
				'relative flex items-center justify-center overflow-hidden',
				className,
			)}
			aria-label={`${name} hybrid creature`}
		>
			{fadeTarget ? (
				<div
					className="pointer-events-none absolute inset-[10%] flex items-center justify-center"
					aria-hidden
				>
					{withArt.map((donor, i) => (
						<SilhouetteFigure
							key={donor.slug}
							name={donor.name}
							art={donor.art}
							className={cn(
								'absolute h-full w-full transition-opacity duration-700',
								motionOk && withArt.length > 1
									? i === fadeIndex
										? 'opacity-90'
										: 'opacity-0'
									: i === 0
										? 'opacity-90'
										: 'opacity-0',
							)}
						/>
					))}
				</div>
			) : (
				<div
					className="absolute inset-0 flex items-center justify-center"
					aria-hidden
				>
					<Dna
						className="size-1/3 text-foreground/20"
						strokeWidth={2}
					/>
				</div>
			)}

			{fadeTarget && (
				<div
					className="relative z-10 flex size-[22%] min-h-9 min-w-9 items-center justify-center rounded-full border border-primary/25 bg-card/85 shadow-sm backdrop-blur"
					aria-hidden
				>
					<Dna
						className="size-[55%] text-primary"
						strokeWidth={2.25}
					/>
				</div>
			)}
		</div>
	);
}
