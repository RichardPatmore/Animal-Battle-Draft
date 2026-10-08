<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Strength stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class StrengthFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Strength;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::MassKg, 0.3),
			new FormulaInput(MeasurementKey::BiteForceN, 0.25),
			new FormulaInput(MeasurementKey::LiftRatio, 0.15),
			new FormulaInput(MeasurementKey::WeaponryScore, 0.2),
			new FormulaInput(MeasurementKey::VenomScore, 0.1),
		];
	}

	public function explanation(): string
	{
		return 'Body mass, bite force and lifting ability plus natural weapons and venom.';
	}
}
