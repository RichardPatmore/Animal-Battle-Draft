<?php

namespace App\Enums;

/**
 * Lifecycle of a draft run.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
enum DraftStatus: string
{
	case InProgress = 'in_progress';
	case Completed = 'completed';
	case Abandoned = 'abandoned';
}
