<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges legacy speed → agility and attack → strength draft picks and drops orphaned stat rows.
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::table('draft_picks')
			->where('stat', 'speed')
			->whereIn('draft_id', DB::table('draft_picks')->select('draft_id')->where('stat', 'agility'))
			->delete();

		DB::table('draft_picks')
			->where('stat', 'attack')
			->whereIn('draft_id', DB::table('draft_picks')->select('draft_id')->where('stat', 'strength'))
			->delete();

		DB::table('draft_picks')->where('stat', 'speed')->update(['stat' => 'agility']);
		DB::table('draft_picks')->where('stat', 'attack')->update(['stat' => 'strength']);

		DB::table('animal_stats')->whereIn('stat', ['speed', 'attack'])->delete();
	}

	public function down(): void
	{
		// Irreversible: merged stats cannot be split again reliably.
	}
};
