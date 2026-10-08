import { Head, Link } from '@inertiajs/react';
import { Copy, RotateCcw, Share2, Swords, Trophy } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import brawl from '@/routes/brawl';
import drafts from '@/routes/drafts';
import type { HybridSummary } from '@/types/game';

type GauntletFight = {
	level: number;
	opponent_slug: string;
	opponent_name: string;
	won: boolean;
};

type GauntletSummary = {
	wins: number;
	peak_level: number;
	hybrid_hp: number;
	max_hp: number;
	history: GauntletFight[];
};

export default function BrawlSummary({
	draft,
	gauntlet,
	share,
}: {
	draft: HybridSummary;
	gauntlet: GauntletSummary;
	share: { url: string; text: string };
}) {
	const name = draft.hybrid_name ?? 'Creature';
	const hpShare =
		gauntlet.max_hp > 0
			? Math.round((gauntlet.hybrid_hp / gauntlet.max_hp) * 100)
			: 0;

	const copyResults = async () => {
		await navigator.clipboard.writeText(share.text);
		toast.success('Results copied to clipboard');
	};

	const shareResults = async () => {
		if (navigator.share) {
			try {
				await navigator.share({
					title: `${name} — gauntlet run`,
					text: share.text,
					url: share.url,
				});
			} catch {
				// user cancelled
			}

			return;
		}

		await copyResults();
	};

	return (
		<>
			<Head title={`Gauntlet summary — ${name}`} />

			<Link
				href={drafts.show(draft.id)}
				className="text-sm text-muted-foreground hover:underline"
			>
				← Back to {name}
			</Link>

			<div className="space-y-2 py-6">
				<p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
					Gauntlet complete
				</p>
				<h1 className="text-3xl font-bold">{name}</h1>
				<p className="max-w-2xl text-muted-foreground">
					Your run in the {draft.category} gauntlet — how far this
					hybrid made it against progressively tougher animals.
				</p>
			</div>

			<section className="grid gap-4 sm:grid-cols-3">
				<div className="rounded-2xl border bg-card p-5 text-center backdrop-blur">
					<p className="text-xs font-semibold text-muted-foreground uppercase">
						Bouts won
					</p>
					<p className="flex items-center justify-center gap-2 text-4xl font-bold text-primary tabular-nums">
						<Trophy className="size-8" />
						{gauntlet.wins}
					</p>
				</div>
				<div className="rounded-2xl border bg-card p-5 text-center backdrop-blur">
					<p className="text-xs font-semibold text-muted-foreground uppercase">
						Peak level
					</p>
					<p className="text-4xl font-bold tabular-nums">
						{gauntlet.peak_level}
					</p>
				</div>
				<div className="rounded-2xl border bg-card p-5 text-center backdrop-blur">
					<p className="text-xs font-semibold text-muted-foreground uppercase">
						HP remaining
					</p>
					<p className="text-4xl font-bold tabular-nums">
						{Math.round(gauntlet.hybrid_hp)}
						<span className="text-lg text-muted-foreground">
							{' '}
							/ {Math.round(gauntlet.max_hp)} ({hpShare}%)
						</span>
					</p>
				</div>
			</section>

			{draft.final_score !== null && (
				<p className="pt-2 text-center text-sm text-muted-foreground">
					Draft build score:{' '}
					<span className="font-semibold text-foreground">
						{draft.final_score}
					</span>
				</p>
			)}

			<section className="mt-8 space-y-3">
				<h2 className="text-lg font-semibold">Fight log</h2>
				<ol className="space-y-2">
					{gauntlet.history.map((fight) => (
						<li
							key={`${fight.level}-${fight.opponent_slug}`}
							className={cn(
								'flex items-center justify-between gap-3 rounded-xl border px-4 py-3 text-sm backdrop-blur',
								fight.won
									? 'border-primary/30 bg-card'
									: 'border-destructive/40 bg-destructive/5',
							)}
						>
							<span>
								<span className="font-medium text-muted-foreground">
									Level {fight.level}
								</span>
								{' · '}
								{fight.opponent_name}
							</span>
							<span
								className={cn(
									'font-semibold',
									fight.won
										? 'text-primary'
										: 'text-destructive',
								)}
							>
								{fight.won ? 'Win' : 'Loss'}
							</span>
						</li>
					))}
				</ol>
			</section>

			<div className="mt-8 flex flex-wrap justify-center gap-3">
				<Button size="lg" onClick={shareResults}>
					<Share2 className="size-4" /> Share results
				</Button>
				<Button size="lg" variant="secondary" onClick={copyResults}>
					<Copy className="size-4" /> Copy to clipboard
				</Button>
				<Button size="lg" variant="secondary" asChild>
					<Link href={brawl.index(draft.id)}>
						<RotateCcw className="size-4" /> Run gauntlet again
					</Link>
				</Button>
				<Button size="lg" variant="ghost" asChild>
					<Link href={drafts.show(draft.id)}>
						<Swords className="size-4" /> View hybrid
					</Link>
				</Button>
			</div>
		</>
	);
}
