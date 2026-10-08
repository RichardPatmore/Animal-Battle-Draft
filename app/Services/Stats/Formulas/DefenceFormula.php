<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;
use App\Enums\Stat;

/**
 * Derives the Defence stat.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class DefenceFormula extends StatFormula
{
	public function stat(): Stat
	{
		return Stat::Defence;
	}

	public function inputs(): array
	{
		return [
			new FormulaInput(MeasurementKey::ArmourScore, 0.4),
			new FormulaInput(MeasurementKey::HideScore, 0.35),
			new FormulaInput(MeasurementKey::MassKg, 0.15),
			new FormulaInput(MeasurementKey::VenomScore, 0.1),
		];
	}

	public function explanation(): string
	{
		return 'Hard armour (shells, plates, scales) and soft protection (thick skin, fat, blubber), helped by size and toxic deterrents.';
	}
}
