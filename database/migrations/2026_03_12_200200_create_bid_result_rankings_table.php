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
        Schema::create('bid_result_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_result_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank')->nullable();
            $table->string('company_name')->nullable();
            $table->unsignedBigInteger('bid_amount')->nullable();
            $table->decimal('bid_rate', 8, 3)->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index(['bid_result_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_result_rankings');
    }
};
