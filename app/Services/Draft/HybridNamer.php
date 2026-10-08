<?php

namespace App\Services\Draft;

use App\Enums\Stat;

/**
 * Builds names like "Swift Cheedile" by blending the two animals behind a hybrid's best stats.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class HybridNamer
{
	private const VOWELS = 'aeiouy';

	/**
	 * @param  array<int, array{stat: Stat, value: int, name: string}>  $picks
	 */
	public function name(array $picks): string
	{
		if ($picks === []) {
			return 'Mystery Beast';
		}

		usort($picks, fn ($a, $b) => $b['value'] <=> $a['value']);

		$first = $this->baseWord($picks[0]['name']);
		$second = $first;

		foreach (array_slice($picks, 1) as $pick) {
			$candidate = $this->baseWord($pick['name']);

			if ($candidate !== $first) {
				$second = $candidate;

				break;
			}
		}

		return $this->epithet($picks[0]['stat']).' '.ucfirst($this->blend($first, $second));
	}

	/**
	 * Fun generated names the player can pick on the result screen.
	 *
	 * @param  array<int, array{stat: Stat, value: int, name: string}>  $picks
	 * @return list<string>
	 */
	public function suggestions(array $picks, int $limit = 4): array
	{
		if ($picks === []) {
			return ['Mystery Beast'];
		}

		$sorted = $picks;
		usort($sorted, fn ($a, $b) => $b['value'] <=> $a['value']);
		$ideas = [];

		foreach ($sorted as $i => $pick) {
			$first = $this->baseWord($pick['name']);
			$second = $first;

			foreach (array_slice($sorted, $i + 1) as $other) {
				$candidate = $this->baseWord($other['name']);

				if ($candidate !== $first) {
					$second = $candidate;

					break;
				}
			}

			$ideas[] = $this->epithet($pick['stat']).' '.ucfirst($this->blend($first, $second));
		}

		$ideas[] = $this->name($picks);

		return array_values(array_slice(array_unique($ideas), 0, $limit));
	}

	/**
	 * Joins the opening syllable of one word to the closing syllable of another.
	 */
	public function blend(string $a, string $b): string
	{
		if ($a === $b) {
			return $a;
		}

		$prefix = $this->prefix($a);
		$suffix = $this->suffix($b);

		if ($prefix !== '' && $suffix !== '' && substr($prefix, -1) === $suffix[0]) {
			$suffix = substr($suffix, 1);
		}

		return $prefix.$suffix;
	}

	public function epithet(Stat $stat): string
	{
		return match ($stat) {
			Stat::Strength => 'Savage',
			Stat::Health => 'Hardy',
			Stat::Defence => 'Armoured',
			Stat::Senses => 'Keen-eyed',
			Stat::Intelligence => 'Cunning',
			Stat::Stealth => 'Shadow',
			Stat::Agility => 'Swift',
		};
	}

	/**
	 * Last word of a common name, letters only ("Great white shark" -> "shark").
	 */
	private function baseWord(string $name): string
	{
		$words = preg_split('/[\s-]+/', strtolower(trim($name))) ?: [$name];
		$word = (string) preg_replace('/[^a-z]/', '', (string) end($words));

		return $word !== '' ? $word : 'beast';
	}

	/**
	 * Up to the end of the first vowel group plus one consonant ("cheetah" -> "cheet").
	 */
	private function prefix(string $word): string
	{
		$len = strlen($word);
		$i = 0;

		while ($i < $len && ! $this->isVowel($word[$i])) {
			$i++;
		}
		while ($i < $len && $this->isVowel($word[$i])) {
			$i++;
		}

		$end = min($len, $i + 1);

		return $end >= $len - 1 ? $word : substr($word, 0, $end);
	}

	/**
	 * From the consonant before the last vowel group, ignoring a silent final "e" ("crocodile" -> "dile").
	 */
	private function suffix(string $word): string
	{
		$searchEnd = strlen($word) - 1;

		if ($searchEnd > 0 && $word[$searchEnd] === 'e') {
			$searchEnd--;
		}

		$i = $searchEnd;
		while ($i >= 0 && ! $this->isVowel($word[$i])) {
			$i--;
		}
		while ($i > 0 && $this->isVowel($word[$i - 1])) {
			$i--;
		}

		return substr($word, max(0, $i - 1));
	}

	private function isVowel(string $char): bool
	{
		return str_contains(self::VOWELS, $char);
	}
}
