<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('sources', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('url')->nullable();
			$table->timestamp('retrieved_at')->nullable();
			$table->text('notes')->nullable();
			$table->timestamps();

			$table->unique(['name', 'url']);
		});

		Schema::create('animals', function (Blueprint $table) {
			$table->id();
			$table->string('slug')->unique();
			$table->string('common_name');
			$table->string('scientific_name')->nullable();
			$table->string('wikidata_id')->nullable()->unique();
			$table->string('wikipedia_title')->nullable();
			$table->unsignedBigInteger('gbif_key')->nullable();
			$table->string('taxon_class')->nullable()->index();
			$table->string('taxon_order')->nullable();
			$table->string('taxon_family')->nullable();
			$table->string('domain')->nullable();
			$table->string('diet')->nullable();
			$table->boolean('is_apex_predator')->default(false);
			$table->string('conservation_status')->nullable();
			$table->string('population_trend')->nullable();
			$table->text('habitat_summary')->nullable();
			$table->json('habitats')->nullable();
			$table->json('regions')->nullable();
			$table->text('description')->nullable();
			$table->string('image_url', 1024)->nullable();
			$table->string('image_credit')->nullable();
			$table->string('image_licence')->nullable();
			$table->boolean('is_published')->default(false)->index();
			$table->timestamps();
		});

		Schema::create('animal_measurements', function (Blueprint $table) {
			$table->id();
			$table->foreignId('animal_id')->constrained()->cascadeOnDelete();
			$table->string('key');
			$table->double('value_min');
			$table->double('value_max')->nullable();
			$table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
			$table->string('confidence')->default('medium');
			$table->boolean('is_manual')->default(false);
			$table->text('notes')->nullable();
			$table->timestamps();

			$table->unique(['animal_id', 'key']);
		});

		Schema::create('animal_stats', function (Blueprint $table) {
			$table->id();
			$table->foreignId('animal_id')->constrained()->cascadeOnDelete();
			$table->string('stat');
			$table->unsignedTinyInteger('derived_value')->nullable();
			$table->unsignedTinyInteger('override_value')->nullable();
			$table->json('breakdown')->nullable();
			$table->text('notes')->nullable();
			$table->timestamps();

			$table->unique(['animal_id', 'stat']);
		});

		Schema::create('animal_facts', function (Blueprint $table) {
			$table->id();
			$table->foreignId('animal_id')->constrained()->cascadeOnDelete();
			$table->string('type')->default('fun');
			$table->text('body');
			$table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
			$table->unsignedSmallInteger('sort_order')->default(0);
			$table->timestamps();
		});

		Schema::create('categories', function (Blueprint $table) {
			$table->id();
			$table->string('slug')->unique();
			$table->string('name');
			$table->text('description')->nullable();
			$table->string('icon')->nullable();
			$table->json('rules')->nullable();
			$table->boolean('size_relative')->default(false);
			$table->boolean('is_active')->default(true);
			$table->unsignedSmallInteger('sort_order')->default(0);
			$table->timestamps();
		});

		Schema::create('animal_category', function (Blueprint $table) {
			$table->foreignId('animal_id')->constrained()->cascadeOnDelete();
			$table->foreignId('category_id')->constrained()->cascadeOnDelete();
			$table->boolean('is_manual')->default(true);

			$table->primary(['animal_id', 'category_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('animal_category');
		Schema::dropIfExists('categories');
		Schema::dropIfExists('animal_facts');
		Schema::dropIfExists('animal_stats');
		Schema::dropIfExists('animal_measurements');
		Schema::dropIfExists('animals');
		Schema::dropIfExists('sources');
	}
};
