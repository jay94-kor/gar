<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bid_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->json('special_conditions')->nullable();
            $table->string('status')->default('pending');
            $table->string('schema_version')->nullable();
            $table->string('prompt_version')->nullable();
            $table->string('model_name')->nullable();
            $table->string('input_hash')->nullable();
            $table->unsignedInteger('analysis_version')->default(1);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('is_current')->default(true);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['bid_id', 'analysis_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_analyses');
    }
};
