<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Stealth stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class StealthFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Stealth;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::CamouflageScore, 0.8),
			new FormulaInput(MeasurementKey::MassKg, 0.2, invert: true),
		];
	}

	public function explanation(): string
	{
		return 'Camouflage and ambush skill, with smaller bodies easier to hide.';
	}
}
