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
        Schema::create('bid_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('performance_type')->nullable();
            $table->text('performance_scope')->nullable();
            $table->unsignedTinyInteger('performance_years')->nullable();
            $table->unsignedBigInteger('min_amount')->nullable();
            $table->unsignedInteger('min_count')->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->json('grade_criteria')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_performances');
    }
};
