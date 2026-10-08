<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Agility stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class AgilityFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Agility;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::TopSpeedKmh, 0.5),
			new FormulaInput(MeasurementKey::AgilityScore, 0.35),
			new FormulaInput(MeasurementKey::MassKg, 0.15, invert: true),
		];
	}

	public function explanation(): string
	{
		return 'Top speed and manoeuvrability, with lighter bodies turning faster.';
	}
}
