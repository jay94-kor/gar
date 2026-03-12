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
        Schema::create('bid_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('result_status')->default('unknown');
            $table->string('awarded_company')->nullable();
            $table->string('awarded_biz_no')->nullable();
            $table->unsignedBigInteger('awarded_amount')->nullable();
            $table->decimal('award_rate', 8, 3)->nullable();
            $table->unsignedInteger('participant_count')->nullable();
            $table->timestamp('award_dt')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['result_status', 'award_dt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_results');
    }
};
