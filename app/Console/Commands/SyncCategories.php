<?php

namespace App\Console\Commands;

use App\Services\Categories\CategoryAssigner;
use Illuminate\Console\Command;

/**
 * Re-applies category rules to all animals.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class SyncCategories extends Command
{
	protected $signature = 'categories:sync';

	protected $description = 'Re-apply rule-based category membership (manual attachments are kept)';

	public function handle(CategoryAssigner $assigner): int
	{
		$counts = $assigner->syncAll();

		$this->table(['Category', 'Animals'], collect($counts)->map(fn ($n, $slug) => [$slug, $n])->values()->all());

		return self::SUCCESS;
	}
}
