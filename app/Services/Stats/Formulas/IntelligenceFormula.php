<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Intelligence stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class IntelligenceFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Intelligence;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::IntelligenceScore, 0.7),
			new FormulaInput(MeasurementKey::EncephalizationQuotient, 0.3),
		];
	}

	public function explanation(): string
	{
		return 'Researched intelligence rating, backed by brain size relative to body (EQ) where known.';
	}
}
