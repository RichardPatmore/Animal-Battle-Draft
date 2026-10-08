<?php

namespace App\Services\Stats\Formulas;

use App\Enums\MeasurementKey;

/**
 * One weighted measurement feeding a stat formula.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
final readonly class FormulaInput
{
	/**
	 * @param  bool  $invert  when true, a lower measurement scores higher (e.g. small body size helps stealth)
	 */
	public function __construct(
		public MeasurementKey $key,
		public float $weight,
		public bool $invert = false,
	) {}
}
