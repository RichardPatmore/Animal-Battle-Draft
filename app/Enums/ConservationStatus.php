<?php

namespace App\Enums;

/**
 * IUCN Red List categories.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum ConservationStatus: string
{
	case Extinct = 'EX';
	case ExtinctInTheWild = 'EW';
	case CriticallyEndangered = 'CR';
	case Endangered = 'EN';
	case Vulnerable = 'VU';
	case NearThreatened = 'NT';
	case LeastConcern = 'LC';
	case DataDeficient = 'DD';
	case NotEvaluated = 'NE';

	public function label(): string
	{
		return match ($this) {
			self::Extinct => 'Extinct',
			self::ExtinctInTheWild => 'Extinct in the Wild',
			self::CriticallyEndangered => 'Critically Endangered',
			self::Endangered => 'Endangered',
			self::Vulnerable => 'Vulnerable',
			self::NearThreatened => 'Near Threatened',
			self::LeastConcern => 'Least Concern',
			self::DataDeficient => 'Data Deficient',
			self::NotEvaluated => 'Not Evaluated',
		};
	}

	/**
	 * Filament colour name for badges.
	 */
	public function color(): string
	{
		return match ($this) {
			self::Extinct, self::ExtinctInTheWild, self::CriticallyEndangered => 'danger',
			self::Endangered, self::Vulnerable => 'warning',
			self::NearThreatened => 'info',
			self::LeastConcern => 'success',
			self::DataDeficient, self::NotEvaluated => 'gray',
		};
	}

	public function isThreatened(): bool
	{
		return in_array($this, [self::CriticallyEndangered, self::Endangered, self::Vulnerable], true);
	}

	/**
	 * Maps Wikidata conservation status items (P141 values) to IUCN categories.
	 */
	public static function fromWikidataId(string $qid): ?self
	{
		return match ($qid) {
			'Q237350' => self::Extinct,
			'Q239509' => self::ExtinctInTheWild,
			'Q219127' => self::CriticallyEndangered,
			'Q11394' => self::Endangered,
			'Q278113' => self::Vulnerable,
			'Q719675' => self::NearThreatened,
			'Q211005' => self::LeastConcern,
			'Q3245245' => self::DataDeficient,
			default => null,
		};
	}

	/**
	 * @return array<string, string>
	 */
	public static function selectOptions(): array
	{
		$options = [];

		foreach (self::cases() as $case) {
			$options[$case->value] = $case->label();
		}

		return $options;
	}
}
