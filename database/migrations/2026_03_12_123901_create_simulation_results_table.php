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
        Schema::create('simulation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('incomplete');
            $table->string('snapshot_hash')->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->decimal('subtotal_without_price', 6, 2)->nullable();
            $table->decimal('required_price_score', 6, 2)->nullable();
            $table->decimal('required_bid_rate', 5, 2)->nullable();
            $table->json('breakdown')->nullable();
            $table->json('missing_fields')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'bid_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_results');
    }
};
