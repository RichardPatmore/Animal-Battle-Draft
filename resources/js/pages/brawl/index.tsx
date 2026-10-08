import { Head, Link, router, usePage } from '@inertiajs/react';
import { Swords, Trophy } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
	BrawlArena,
	type FightFrame,
	type GauntletOpponent,
} from '@/components/game/brawl-arena';
import { Button } from '@/components/ui/button';
import brawl from '@/routes/brawl';
import drafts from '@/routes/drafts';
import type { HybridSummary, StatOption } from '@/types/game';

type GauntletSession = {
	level: number;
	wins: number;
	hybrid_hp: number;
	max_hp: number;
	active: boolean;
};

type FightResponse = {
	fight: {
		hybrid_won: boolean;
		hybrid_hp: number;
		opponent_hp: number;
		hybrid_max: number;
		opponent_max: number;
		frames: FightFrame[];
	};
	session: GauntletSession;
	opponent: GauntletOpponent;
	next_opponent: GauntletOpponent | null;
};

type GauntletStep = 'ready' | 'animating' | 'outcome';

type PageProps = {
	draft: HybridSummary;
	stats: StatOption[];
	session: GauntletSession | null;
	opponent: GauntletOpponent | null;
	ranked_count: number;
	hybrid_damage_factor: number;
};

function sessionFingerprint(session: GauntletSession | null): string {
	if (!session) {
		return 'none';
	}

	return `${session.level}:${session.wins}:${session.hybrid_hp}:${session.max_hp}:${session.active}`;
}

function sleep(ms: number): Promise<void> {
	return new Promise((resolve) => {
		window.setTimeout(resolve, ms);
	});
}

export default function BrawlGauntlet() {
	const {
		draft,
		session: serverSession,
		opponent: serverOpponent,
		ranked_count,
	} = usePage<PageProps>().props;

	const serverBoot = useMemo(
		() =>
			`${sessionFingerprint(serverSession)}|${serverOpponent?.slug ?? ''}`,
		[serverSession, serverOpponent?.slug],
	);

	const [session, setSession] = useState(serverSession);
	const [opponent, setOpponent] = useState(serverOpponent);
	const [oppMax, setOppMax] = useState(serverOpponent?.combat_max_hp ?? 100);
	const [step, setStep] = useState<GauntletStep>('ready');
	const fightLockRef = useRef(false);
	const lastServerBootRef = useRef(serverBoot);
	const playbackRunRef = useRef(0);

	const [arenaHp, setArenaHp] = useState<{
		hybrid: number;
		opponent: number;
	} | null>(null);
	const [battleText, setBattleText] = useState<string | null>(null);
	const [outcome, setOutcome] = useState<string | null>(null);
	const [lastWon, setLastWon] = useState<boolean | null>(null);
	const [nextOpponent, setNextOpponent] = useState<GauntletOpponent | null>(
		null,
	);

	useEffect(() => {
		if (fightLockRef.current) {
			return;
		}

		if (lastServerBootRef.current === serverBoot) {
			return;
		}

		lastServerBootRef.current = serverBoot;
		setSession(serverSession);
		setOpponent(serverOpponent);
		setOppMax(serverOpponent?.combat_max_hp ?? 100);
		setStep('ready');
		setArenaHp(null);
		setBattleText(null);
		setOutcome(null);
		setLastWon(null);
		setNextOpponent(null);
	}, [serverBoot, serverSession, serverOpponent]);

	const hybridMax = session?.max_hp ?? 100;
	const headerHp = session?.hybrid_hp ?? hybridMax;
	const name = draft.hybrid_name ?? 'Creature';

	const displayHybridHp = arenaHp?.hybrid ?? headerHp;
	const displayOpponentHp = arenaHp?.opponent ?? oppMax;

	const finishFight = (data: FightResponse) => {
		setSession(data.session);
		setArenaHp({
			hybrid: data.fight.hybrid_hp,
			opponent: data.fight.opponent_hp,
		});
		setLastWon(data.fight.hybrid_won);
		setOutcome(data.fight.hybrid_won ? 'Victory!' : 'Knocked out!');
		setBattleText(null);

		if (data.session.active && data.next_opponent) {
			setNextOpponent(data.next_opponent);
		} else {
			setNextOpponent(null);
		}

		fightLockRef.current = false;
		setStep('outcome');
	};

	const startGauntlet = () => {
		router.post(
			brawl.start(draft.id).url,
			{},
			{
				preserveState: false,
				preserveScroll: true,
				only: ['session', 'opponent'],
			},
		);
	};

	const runFight = async () => {
		if (
			!session?.active ||
			!opponent ||
			fightLockRef.current ||
			step !== 'ready'
		) {
			return;
		}

		const runId = ++playbackRunRef.current;
		fightLockRef.current = true;
		setStep('animating');
		setOutcome(null);
		setNextOpponent(null);
		setArenaHp({ hybrid: headerHp, opponent: oppMax });
		setBattleText(null);

		const res = await fetch(brawl.fight(draft.id).url, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'X-XSRF-TOKEN': decodeURIComponent(
					document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
				),
			},
			credentials: 'same-origin',
		});

		if (!res.ok) {
			fightLockRef.current = false;
			setStep('ready');
			setArenaHp(null);

			return;
		}

		const data = (await res.json()) as FightResponse;
		setOppMax(data.fight.opponent_max);

		const frames = data.fight.frames;

		if (frames.length === 0) {
			finishFight(data);

			return;
		}

		for (const frame of frames) {
			if (playbackRunRef.current !== runId) {
				return;
			}

			setArenaHp({
				hybrid: frame.hybrid_hp,
				opponent: frame.opponent_hp,
			});
			setBattleText(frame.text ?? null);
			await sleep(550);
		}

		if (playbackRunRef.current !== runId) {
			return;
		}

		finishFight(data);
	};

	const continueToNext = () => {
		if (!nextOpponent) {
			return;
		}

		setOpponent(nextOpponent);
		setOppMax(nextOpponent.combat_max_hp);
		setNextOpponent(null);
		setOutcome(null);
		setLastWon(null);
		setArenaHp(null);
		setBattleText(null);
		setStep('ready');
	};

	const canFight =
		session?.active &&
		opponent &&
		step === 'ready' &&
		!fightLockRef.current;

	const message =
		step === 'animating' ? (battleText ?? 'Fight in progress…') : outcome;

	return (
		<>
			<Head title={`Gauntlet – ${name}`} />

			<Link
				href={drafts.show(draft.id)}
				className="text-sm text-muted-foreground hover:underline"
			>
				← Back to {name}
			</Link>

			<div className="space-y-2 py-6">
				<h1 className="flex items-center gap-2 text-3xl font-bold">
					<Swords className="size-8 text-primary" /> Gauntlet
				</h1>
				<p className="max-w-2xl text-muted-foreground">
					Face animals one after another, ranked by base stat total.
					Each level gets tougher. Your HP carries over — how many
					bouts can {name} survive?
				</p>
			</div>

			{!session && (
				<div className="rounded-2xl border bg-card p-8 text-center backdrop-blur">
					<p className="mb-4 text-muted-foreground">
						{ranked_count} challengers wait in the roster, weakest
						first.
					</p>
					<Button size="lg" onClick={startGauntlet}>
						Enter the arena
					</Button>
				</div>
			)}

			{session && (
				<div className="space-y-6">
					<div className="flex flex-wrap items-center justify-center gap-6 text-center">
						<div>
							<p className="text-xs font-semibold text-muted-foreground uppercase">
								Level
							</p>
							<p className="text-3xl font-bold tabular-nums">
								{session.level}
							</p>
						</div>
						<div>
							<p className="text-xs font-semibold text-muted-foreground uppercase">
								Wins
							</p>
							<p className="flex items-center justify-center gap-1 text-3xl font-bold text-primary tabular-nums">
								<Trophy className="size-6" />
								{session.wins}
							</p>
						</div>
						<div>
							<p className="text-xs font-semibold text-muted-foreground uppercase">
								Your HP
							</p>
							<p className="text-3xl font-bold tabular-nums">
								{Math.round(headerHp)} /{' '}
								{Math.round(session.max_hp)}
							</p>
						</div>
					</div>

					<BrawlArena
						hybridName={name}
						hybridContributors={draft.contributors}
						opponent={opponent}
						hybridMax={hybridMax}
						opponentMax={oppMax}
						hybridHp={displayHybridHp}
						opponentHp={displayOpponentHp}
						battleText={message}
						animating={step === 'animating'}
					/>

					{step === 'ready' && session.active && opponent && (
						<div className="flex justify-center gap-3">
							<Button
								size="lg"
								disabled={!canFight}
								onClick={runFight}
							>
								Fight {opponent.name}
							</Button>
							<Button
								size="lg"
								variant="secondary"
								disabled={fightLockRef.current}
								onClick={startGauntlet}
							>
								Restart gauntlet
							</Button>
						</div>
					)}

					{step === 'outcome' &&
						session.active &&
						lastWon &&
						nextOpponent && (
							<div className="flex flex-col items-center gap-3">
								<p className="text-sm text-muted-foreground">
									Next up:{' '}
									<span className="font-semibold text-foreground">
										{nextOpponent.name}
									</span>{' '}
									(level {nextOpponent.level})
								</p>
								<Button size="lg" onClick={continueToNext}>
									Continue to next challenger
								</Button>
							</div>
						)}

					{step === 'outcome' && !session.active && (
						<div className="flex flex-col items-center gap-3 rounded-2xl border bg-card p-6 text-center backdrop-blur">
							<p className="text-lg font-semibold">
								Run over — {session.wins} win
								{session.wins === 1 ? '' : 's'}!
							</p>
							<div className="flex flex-wrap justify-center gap-2">
								{session.wins > 0 && (
									<Button size="lg" asChild>
										<Link
											href={brawl.summary(draft.id).url}
										>
											View gauntlet summary
										</Link>
									</Button>
								)}
								<Button
									size="lg"
									variant="secondary"
									onClick={startGauntlet}
								>
									Try again
								</Button>
							</div>
						</div>
					)}
				</div>
			)}
		</>
	);
}
