<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Senses stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class SensesFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Senses;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::VisionScore, 1 / 3),
			new FormulaInput(MeasurementKey::HearingScore, 1 / 3),
			new FormulaInput(MeasurementKey::SmellScore, 1 / 3),
		];
	}

	public function explanation(): string
	{
		return 'Equal parts vision, hearing and smell.';
	}
}
