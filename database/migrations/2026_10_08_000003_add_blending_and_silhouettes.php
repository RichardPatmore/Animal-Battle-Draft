<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('draft_picks', function (Blueprint $table) {
			$table->json('animal_stats')->nullable()->after('value');
		});

		Schema::table('animals', function (Blueprint $table) {
			$table->string('silhouette_path')->nullable()->after('image_licence');
			$table->string('silhouette_credit')->nullable()->after('silhouette_path');
			$table->string('silhouette_licence')->nullable()->after('silhouette_credit');
			$table->string('silhouette_source_url')->nullable()->after('silhouette_licence');
			$table->string('silhouette_taxon')->nullable()->after('silhouette_source_url');
		});
	}

	public function down(): void
	{
		Schema::table('draft_picks', function (Blueprint $table) {
			$table->dropColumn('animal_stats');
		});

		Schema::table('animals', function (Blueprint $table) {
			$table->dropColumn(['silhouette_path', 'silhouette_credit', 'silhouette_licence', 'silhouette_source_url', 'silhouette_taxon']);
		});
	}
};
