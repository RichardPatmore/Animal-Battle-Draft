<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Health stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class HealthFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Health;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::MassKg, 0.6),
			new FormulaInput(MeasurementKey::LengthM, 0.2),
			new FormulaInput(MeasurementKey::LifespanYears, 0.2),
		];
	}

	public function explanation(): string
	{
		return 'Bigger, longer-lived animals can soak up more damage.';
	}
}
