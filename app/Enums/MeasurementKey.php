<?php

namespace App\Enums;

/**
 * Real-world measurements that feed stat derivation.
 *
 * Physical keys hold real units. Score keys are researched 0-10 ratings for traits that have no
 * single comparable unit across species (e.g. camouflage).
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum MeasurementKey: string
{
	case MassKg = 'mass_kg';
	case LengthM = 'length_m';
	case TopSpeedKmh = 'top_speed_kmh';
	case BiteForceN = 'bite_force_n';
	case LiftRatio = 'lift_ratio';
	case LifespanYears = 'lifespan_years';
	case EncephalizationQuotient = 'encephalization_quotient';
	case VisionScore = 'vision_score';
	case HearingScore = 'hearing_score';
	case SmellScore = 'smell_score';
	case ArmourScore = 'armour_score';
	case HideScore = 'hide_score';
	case WeaponryScore = 'weaponry_score';
	case VenomScore = 'venom_score';
	case CamouflageScore = 'camouflage_score';
	case AgilityScore = 'agility_score';
	case IntelligenceScore = 'intelligence_score';

	public function label(): string
	{
		return match ($this) {
			self::MassKg => 'Mass',
			self::LengthM => 'Length',
			self::TopSpeedKmh => 'Top speed',
			self::BiteForceN => 'Bite force',
			self::LiftRatio => 'Lift (x body weight)',
			self::LifespanYears => 'Lifespan',
			self::EncephalizationQuotient => 'Encephalization quotient',
			self::VisionScore => 'Vision',
			self::HearingScore => 'Hearing',
			self::SmellScore => 'Smell',
			self::ArmourScore => 'Armour',
			self::HideScore => 'Hide & fat',
			self::WeaponryScore => 'Weaponry',
			self::VenomScore => 'Venom',
			self::CamouflageScore => 'Camouflage',
			self::AgilityScore => 'Agility',
			self::IntelligenceScore => 'Intelligence',
		};
	}

	public function unit(): string
	{
		return match ($this) {
			self::MassKg => 'kg',
			self::LengthM => 'm',
			self::TopSpeedKmh => 'km/h',
			self::BiteForceN => 'N',
			self::LiftRatio => 'x',
			self::LifespanYears => 'years',
			self::EncephalizationQuotient => 'EQ',
			default => '/10',
		};
	}

	/**
	 * Score keys are 0-10 research ratings rather than physical measurements.
	 */
	public function isScore(): bool
	{
		return str_ends_with($this->value, '_score');
	}

	/**
	 * Whether the value spans orders of magnitude across species and should be compared on a log scale.
	 */
	public function isLogScaled(): bool
	{
		return in_array($this, [self::MassKg, self::LengthM, self::BiteForceN, self::LiftRatio, self::TopSpeedKmh, self::LifespanYears], true);
	}

	public function description(): string
	{
		return match ($this) {
			self::MassKg => 'Typical adult body mass.',
			self::LengthM => 'Typical adult body length (head and body, excluding tail where possible).',
			self::TopSpeedKmh => 'Fastest recorded speed in its main medium (land, air or water).',
			self::BiteForceN => 'Measured or estimated bite force in newtons.',
			self::LiftRatio => 'How many times its own body weight it can lift or pull.',
			self::LifespanYears => 'Typical lifespan in the wild.',
			self::EncephalizationQuotient => 'Brain mass relative to the expected brain mass for its body size.',
			self::VisionScore => '0-10 rating of visual acuity, range and low-light vision.',
			self::HearingScore => '0-10 rating of hearing range and sensitivity (includes echolocation).',
			self::SmellScore => '0-10 rating of sense of smell and chemical detection.',
			self::ArmourScore => '0-10 rating of hard protection: shells, bony plates, tough scales, spines and quills.',
			self::HideScore => '0-10 rating of soft protection: thick skin, fat, blubber and dense fur that absorb blows.',
			self::WeaponryScore => '0-10 rating of teeth, claws, horns, tusks or stingers.',
			self::VenomScore => '0-10 rating of venom or toxin potency (0 if none).',
			self::CamouflageScore => '0-10 rating of camouflage, silence and ambush behaviour.',
			self::AgilityScore => '0-10 rating of manoeuvrability, reflexes and acceleration.',
			self::IntelligenceScore => '0-10 rating of problem solving, tool use and learning.',
		};
	}

	/**
	 * @return array<string, string>
	 */
	public static function selectOptions(): array
	{
		$options = [];

		foreach (self::cases() as $case) {
			$options[$case->value] = $case->label().' ('.$case->unit().')';
		}

		return $options;
	}
}
