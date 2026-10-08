import type { StatKey, StatOption } from '@/types/game';

type Series = {
	label: string;
	values: Partial<Record<StatKey, number | null>>;
	className: string;
};

/**
 * Dependency-free SVG radar chart for one or two stat blocks.
 */
export function StatRadar({
	stats,
	series,
	size = 280,
}: {
	stats: StatOption[];
	series: Series[];
	size?: number;
}) {
	const centre = size / 2;
	const radius = size / 2 - 38;
	const angle = (i: number) => (Math.PI * 2 * i) / stats.length - Math.PI / 2;
	const point = (i: number, value: number) => {
		const r = (Math.max(0, Math.min(100, value)) / 100) * radius;

		return [
			centre + r * Math.cos(angle(i)),
			centre + r * Math.sin(angle(i)),
		];
	};

	return (
		<svg
			viewBox={`0 0 ${size} ${size}`}
			className="h-auto w-full max-w-xs"
			role="img"
			aria-label="Stat radar chart"
		>
			{[25, 50, 75, 100].map((ring) => (
				<polygon
					key={ring}
					points={stats
						.map((_, i) => point(i, ring).join(','))
						.join(' ')}
					className="fill-none stroke-foreground/10"
				/>
			))}
			{stats.map((stat, i) => {
				const [x, y] = point(i, 100);
				const [lx, ly] = point(i, 122);

				return (
					<g key={stat.value}>
						<line
							x1={centre}
							y1={centre}
							x2={x}
							y2={y}
							className="stroke-foreground/10"
						/>
						<text
							x={lx}
							y={ly}
							textAnchor="middle"
							dominantBaseline="middle"
							className="fill-muted-foreground text-[10px] font-semibold"
						>
							{stat.label}
						</text>
					</g>
				);
			})}
			{series.map((s) => (
				<polygon
					key={s.label}
					points={stats
						.map((stat, i) =>
							point(i, s.values[stat.value] ?? 0).join(','),
						)
						.join(' ')}
					className={s.className}
					strokeWidth={2}
				>
					<title>{s.label}</title>
				</polygon>
			))}
		</svg>
	);
}
