<?php

namespace App\Services\Stats\Formulas;

use App\Enums\Stat;

/**
 * Describes how a stat is built from measurements. Missing inputs are skipped and the
 * remaining weights are rescaled, so an animal only needs some of the inputs researched.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
abstract class StatFormula
{
	abstract public function stat(): Stat;

	/**
	 * @return list<FormulaInput>
	 */
	abstract public function inputs(): array;

	/**
	 * Plain-English summary shown in the admin breakdown.
	 */
	abstract public function explanation(): string;
}
