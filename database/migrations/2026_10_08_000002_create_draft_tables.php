<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('drafts', function (Blueprint $table) {
			$table->id();
			$table->ulid('public_id')->unique();
			$table->string('session_id')->index();
			$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
			$table->unsignedInteger('seed');
			$table->string('status')->default('in_progress');
			$table->json('current_offer')->nullable();
			$table->unsignedSmallInteger('final_score')->nullable();
			$table->string('hybrid_name')->nullable();
			$table->json('synergies')->nullable();
			$table->timestamp('completed_at')->nullable();
			$table->timestamps();
		});

		Schema::create('draft_picks', function (Blueprint $table) {
			$table->id();
			$table->foreignId('draft_id')->constrained()->cascadeOnDelete();
			$table->unsignedTinyInteger('round');
			$table->json('offered_animal_ids');
			$table->foreignId('animal_id')->constrained()->cascadeOnDelete();
			$table->string('stat');
			$table->unsignedTinyInteger('value');
			$table->timestamps();

			$table->unique(['draft_id', 'stat']);
			$table->unique(['draft_id', 'animal_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('draft_picks');
		Schema::dropIfExists('drafts');
	}
};
