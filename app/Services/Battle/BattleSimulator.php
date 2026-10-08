<?php

namespace App\Services\Battle;

use App\Enums\Stat;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Simulates a fight between two stat blocks.
 *
 * Head-to-head "edges" compare complementary stats (e.g. one side's Senses against the other's
 * Stealth) and turn the gap into a probability with a logistic curve. The overall win chance
 * comes from a seeded Monte Carlo run of many short turn-based fights, and one of those fights
 * is narrated as the battle log.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class BattleSimulator
{
	public const SIMULATIONS = 500;

	private const MAX_ROUNDS = 12;

	/** Steepness of the logistic curve used for edges. */
	private const EDGE_STEEPNESS = 0.06;

	/** Hybrid offence is toned down in gauntlet mode so stacked drafts do not auto-win. */
	public const GAUNTLET_HYBRID_DAMAGE = 0.78;

	/**
	 * @param  array<string, int>  $a  challenger stats
	 * @param  array<string, int>  $b  opponent stats
	 * @return array{win_chance: float, edges: list<array<string, mixed>>, log: list<array<string, mixed>>, winner: string, rounds: int}
	 */
	public function simulate(array $a, array $b, string $nameA, string $nameB, int $seed): array
	{
		$a = Stat::normalizeBlock($a);
		$b = Stat::normalizeBlock($b);
		$randomizer = new Randomizer(new Mt19937($seed));
		$wins = 0;

		for ($i = 0; $i < self::SIMULATIONS; $i++) {
			if ($this->fight($a, $b, $randomizer)['winner'] === 'a') {
				$wins++;
			}
		}

		$story = $this->fight($a, $b, new Randomizer(new Mt19937($seed + 1)), [$nameA, $nameB]);

		return [
			'win_chance' => round($wins / self::SIMULATIONS * 100, 1),
			'edges' => $this->edges($a, $b),
			'log' => $story['log'],
			'winner' => $story['winner'],
			'rounds' => $story['rounds'],
		];
	}

	/**
	 * One gauntlet duel with carry-over hybrid HP. Returns animation frames for the front end.
	 *
	 * @param  array<string, int>  $hybridStats
	 * @param  array<string, int>  $opponentStats
	 * @return array{
	 *     hybrid_won: bool,
	 *     hybrid_hp: float,
	 *     opponent_hp: float,
	 *     hybrid_max: float,
	 *     opponent_max: float,
	 *     frames: list<array{phase: string, hybrid_hp: float, opponent_hp: float, text?: string}>,
	 *     log: list<array<string, mixed>>
	 * }
	 */
	public function gauntletDuel(
		array $hybridStats,
		array $opponentStats,
		float $hybridHp,
		string $hybridName,
		string $opponentName,
		int $seed,
	): array {
		$hybridStats = Stat::normalizeBlock($hybridStats);
		$opponentStats = Stat::normalizeBlock($opponentStats);

		$hybridMax = max($hybridHp, $this->maxHp($hybridStats));
		$opponentMax = $this->maxHp($opponentStats);

		$fighters = [
			'a' => ['stats' => $hybridStats, 'hp' => $hybridHp, 'max' => $hybridMax, 'name' => $hybridName, 'gauntlet' => true],
			'b' => ['stats' => $opponentStats, 'hp' => $opponentMax, 'max' => $opponentMax, 'name' => $opponentName, 'gauntlet' => false],
		];

		$rng = new Randomizer(new Mt19937($seed));
		$frames = [
			[
				'phase' => 'intro',
				'hybrid_hp' => $fighters['a']['hp'],
				'opponent_hp' => $fighters['b']['hp'],
				'text' => "Level challenger: {$opponentName}",
			],
		];
		$log = [];

		$pushFrame = function (string $phase, ?string $text = null) use (&$fighters, &$frames): void {
			$frames[] = [
				'phase' => $phase,
				'hybrid_hp' => max(0, $fighters['a']['hp']),
				'opponent_hp' => max(0, $fighters['b']['hp']),
				'text' => $text,
			];
		};

		$ambushChance = fn (string $x, string $y) => max(0.0, ($fighters[$x]['stats'][Stat::Stealth->value] - $fighters[$y]['stats'][Stat::Senses->value]) / 150);

		foreach (['a' => 'b', 'b' => 'a'] as $x => $y) {
			if ($rng->getFloat(0, 1) < $ambushChance($x, $y)) {
				$damage = $this->damage($fighters[$x]['stats'], $fighters[$y]['stats'], $rng, $fighters[$x]['gauntlet']) * 1.5;
				$fighters[$y]['hp'] -= $damage;
				$text = "{$fighters[$x]['name']} ambushes from cover!";
				$log[] = ['round' => 0, 'actor' => $x, 'text' => $text, 'damage' => (int) round($damage)];
				$pushFrame('hit', $text);
				break;
			}
		}

		$round = 0;

		while ($round < self::MAX_ROUNDS && $fighters['a']['hp'] > 0 && $fighters['b']['hp'] > 0) {
			$round++;
			$pushFrame('clash', 'They crash together!');
			$order = $this->initiative($fighters['a']['stats'], $fighters['b']['stats'], $rng) ? ['a', 'b'] : ['b', 'a'];

			foreach ($order as $x) {
				$y = $x === 'a' ? 'b' : 'a';

				if ($fighters[$x]['hp'] <= 0 || $fighters[$y]['hp'] <= 0) {
					break;
				}

				$attacker = $fighters[$x]['stats'];
				$defender = $fighters[$y]['stats'];
				$isHybrid = $fighters[$x]['gauntlet'];

				if ($rng->getFloat(0, 1) > $this->hitChance($attacker, $defender)) {
					$text = $this->missLine($fighters[$x]['name'], $fighters[$y]['name'], $defender);
					$log[] = ['round' => $round, 'actor' => $x, 'text' => $text, 'damage' => 0];
					$pushFrame('miss', $text);

					continue;
				}

				$damage = $this->damage($attacker, $defender, $rng, $isHybrid);
				$fighters[$y]['hp'] -= $damage;
				$text = $this->hitLine($fighters[$x]['name'], $fighters[$y]['name'], $attacker, $damage, $fighters[$y]['max']);
				$log[] = ['round' => $round, 'actor' => $x, 'text' => $text, 'damage' => (int) round($damage)];
				$pushFrame($x === 'a' ? 'hybrid_hit' : 'opponent_hit', $text);
			}
		}

		$aShare = $fighters['a']['hp'] / max(1, $fighters['a']['max']);
		$bShare = $fighters['b']['hp'] / max(1, $fighters['b']['max']);
		$hybridWon = $aShare >= $bShare && $fighters['a']['hp'] > 0;

		if ($fighters['a']['hp'] <= 0) {
			$hybridWon = false;
		}

		if ($fighters['b']['hp'] <= 0) {
			$hybridWon = true;
		}

		$endText = $hybridWon
			? "{$hybridName} wins the bout!"
			: "{$opponentName} knocks {$hybridName} out of the gauntlet.";

		$log[] = ['round' => $round, 'actor' => $hybridWon ? 'a' : 'b', 'text' => $endText, 'damage' => 0, 'final' => true];
		$pushFrame('end', $endText);

		return [
			'hybrid_won' => $hybridWon,
			'hybrid_hp' => max(0, $fighters['a']['hp']),
			'opponent_hp' => max(0, $fighters['b']['hp']),
			'hybrid_max' => $hybridMax,
			'opponent_max' => $opponentMax,
			'frames' => $frames,
			'log' => $log,
		];
	}

	/**
	 * Matchup-by-matchup comparison, each expressed as the challenger's chance of winning that exchange.
	 *
	 * @param  array<string, int>  $a
	 * @param  array<string, int>  $b
	 * @return list<array<string, mixed>>
	 */
	public function edges(array $a, array $b): array
	{
		$a = Stat::normalizeBlock($a);
		$b = Stat::normalizeBlock($b);
		$s = fn (array $x, Stat $stat) => (float) ($x[$stat->value] ?? 0);

		$matchups = [
			['label' => 'Mobility', 'description' => 'Agility decides who controls the distance.',
				'a' => $s($a, Stat::Agility), 'b' => $s($b, Stat::Agility)],
			['label' => 'Ambush', 'description' => 'Your Stealth against their Senses decides who strikes first.',
				'a' => $s($a, Stat::Stealth), 'b' => $s($b, Stat::Senses)],
			['label' => 'Awareness', 'description' => 'Your Senses against their Stealth stops you being ambushed.',
				'a' => $s($a, Stat::Senses), 'b' => $s($b, Stat::Stealth)],
			['label' => 'Offence', 'description' => 'Your Strength against their Defence and Health.',
				'a' => $s($a, Stat::Strength), 'b' => 0.6 * $s($b, Stat::Defence) + 0.4 * $s($b, Stat::Health)],
			['label' => 'Survival', 'description' => 'Your Defence and Health against their Strength.',
				'a' => 0.6 * $s($a, Stat::Defence) + 0.4 * $s($a, Stat::Health), 'b' => $s($b, Stat::Strength)],
			['label' => 'Tactics', 'description' => 'Intelligence helps dodge blows and pick the right moment.',
				'a' => $s($a, Stat::Intelligence), 'b' => $s($b, Stat::Intelligence)],
		];

		return array_map(fn (array $m) => [
			...$m,
			'a' => round($m['a']),
			'b' => round($m['b']),
			'chance' => round(self::logistic($m['a'] - $m['b']) * 100),
		], $matchups);
	}

	public static function logistic(float $difference): float
	{
		return 1 / (1 + exp(-self::EDGE_STEEPNESS * $difference));
	}

	/**
	 * One turn-based fight. Each fighter has HP from Health, hits based on Senses/Agility versus the
	 * target's Agility/Stealth, and deals damage from Strength reduced by the target's Defence.
	 *
	 * @param  array<string, int>  $a
	 * @param  array<string, int>  $b
	 * @param  array{0: string, 1: string}|null  $names  when given, a narrated log is produced
	 * @return array{winner: string, rounds: int, log: list<array<string, mixed>>}
	 */
	private function fight(array $a, array $b, Randomizer $rng, ?array $names = null): array
	{
		$fighters = [
			'a' => ['stats' => $a, 'hp' => $this->maxHp($a), 'max' => $this->maxHp($a), 'name' => $names[0] ?? 'A', 'gauntlet' => false],
			'b' => ['stats' => $b, 'hp' => $this->maxHp($b), 'max' => $this->maxHp($b), 'name' => $names[1] ?? 'B', 'gauntlet' => false],
		];
		$log = [];
		$narrate = $names !== null;

		$ambushChance = fn (string $x, string $y) => max(0.0, ($fighters[$x]['stats'][Stat::Stealth->value] - $fighters[$y]['stats'][Stat::Senses->value]) / 150);

		foreach (['a' => 'b', 'b' => 'a'] as $x => $y) {
			if ($rng->getFloat(0, 1) < $ambushChance($x, $y)) {
				$damage = $this->damage($fighters[$x]['stats'], $fighters[$y]['stats'], $rng, false) * 1.5;
				$fighters[$y]['hp'] -= $damage;

				if ($narrate) {
					$log[] = ['round' => 0, 'actor' => $x, 'text' => "{$fighters[$x]['name']} ambushes from cover and lands a surprise strike!", 'damage' => (int) round($damage)];
				}

				break;
			}
		}

		$round = 0;

		while ($round < self::MAX_ROUNDS && $fighters['a']['hp'] > 0 && $fighters['b']['hp'] > 0) {
			$round++;
			$order = $this->initiative($fighters['a']['stats'], $fighters['b']['stats'], $rng) ? ['a', 'b'] : ['b', 'a'];

			foreach ($order as $x) {
				$y = $x === 'a' ? 'b' : 'a';

				if ($fighters[$x]['hp'] <= 0 || $fighters[$y]['hp'] <= 0) {
					break;
				}

				$attacker = $fighters[$x]['stats'];
				$defender = $fighters[$y]['stats'];

				if ($rng->getFloat(0, 1) > $this->hitChance($attacker, $defender)) {
					if ($narrate) {
						$log[] = ['round' => $round, 'actor' => $x, 'text' => $this->missLine($fighters[$x]['name'], $fighters[$y]['name'], $defender), 'damage' => 0];
					}

					continue;
				}

				$damage = $this->damage($attacker, $defender, $rng, false);
				$fighters[$y]['hp'] -= $damage;

				if ($narrate) {
					$log[] = ['round' => $round, 'actor' => $x, 'text' => $this->hitLine($fighters[$x]['name'], $fighters[$y]['name'], $attacker, $damage, $fighters[$y]['max']), 'damage' => (int) round($damage)];
				}
			}
		}

		$aShare = $fighters['a']['hp'] / $fighters['a']['max'];
		$bShare = $fighters['b']['hp'] / $fighters['b']['max'];
		$winner = $aShare >= $bShare ? 'a' : 'b';

		if ($narrate) {
			$loser = $winner === 'a' ? 'b' : 'a';
			$text = $fighters[$loser]['hp'] <= 0
				? "{$fighters[$winner]['name']} wins! {$fighters[$loser]['name']} can't fight on."
				: "Both fighters are exhausted. {$fighters[$winner]['name']} is left standing stronger and wins on points.";
			$log[] = ['round' => $round, 'actor' => $winner, 'text' => $text, 'damage' => 0, 'final' => true];
		}

		return ['winner' => $winner, 'rounds' => $round, 'log' => $log];
	}

	/**
	 * @param  array<string, int>  $s
	 */
	private function maxHp(array $s): float
	{
		return 60 + 1.4 * $s[Stat::Health->value];
	}

	/**
	 * @param  array<string, int>  $a
	 * @param  array<string, int>  $b
	 */
	private function initiative(array $a, array $b, Randomizer $rng): bool
	{
		$aScore = $a[Stat::Agility->value] + $rng->getInt(0, 60);
		$bScore = $b[Stat::Agility->value] + $rng->getInt(0, 60);

		return $aScore >= $bScore;
	}

	/**
	 * @param  array<string, int>  $attacker
	 * @param  array<string, int>  $defender
	 */
	private function hitChance(array $attacker, array $defender): float
	{
		$accuracy = 0.5 * $attacker[Stat::Senses->value] + 0.3 * $attacker[Stat::Agility->value] + 0.2 * $attacker[Stat::Intelligence->value];
		$evasion = 0.5 * $defender[Stat::Agility->value] + 0.3 * $defender[Stat::Stealth->value] + 0.2 * $defender[Stat::Intelligence->value];

		return max(0.2, min(0.95, 0.65 + ($accuracy - $evasion) / 200));
	}

	/**
	 * @param  array<string, int>  $attacker
	 * @param  array<string, int>  $defender
	 */
	private function damage(array $attacker, array $defender, Randomizer $rng, bool $hybridGauntlet): float
	{
		$power = (float) $attacker[Stat::Strength->value];
		$mitigation = 1 - $defender[Stat::Defence->value] / 160;
		$raw = max(1.0, (6 + $power * 0.45) * $mitigation * $rng->getFloat(0.7, 1.3));

		if ($hybridGauntlet) {
			$raw *= self::GAUNTLET_HYBRID_DAMAGE;
		}

		return $raw;
	}

	/**
	 * @param  array<string, int>  $attacker
	 */
	private function hitLine(string $x, string $y, array $attacker, float $damage, float $targetMax): string
	{
		$move = $attacker[Stat::Strength->value] >= 70 ? 'slams into' : 'strikes';
		$share = $damage / $targetMax;

		return match (true) {
			$share > 0.3 => "{$x} {$move} {$y} with a devastating blow!",
			$share > 0.15 => "{$x} {$move} {$y} hard.",
			default => "{$x} lands a glancing hit on {$y}.",
		};
	}

	/**
	 * @param  array<string, int>  $defender
	 */
	private function missLine(string $x, string $y, array $defender): string
	{
		return $defender[Stat::Agility->value] >= $defender[Stat::Stealth->value]
			? "{$y} dodges {$x}'s attack."
			: "{$y} slips out of sight and {$x} strikes at nothing.";
	}
}
