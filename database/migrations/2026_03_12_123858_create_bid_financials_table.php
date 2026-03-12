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
        Schema::create('bid_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('eval_method')->nullable();
            $table->string('min_credit_grade')->nullable();
            $table->decimal('max_debt_ratio', 8, 2)->nullable();
            $table->decimal('min_current_ratio', 8, 2)->nullable();
            $table->unsignedBigInteger('min_equity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_financials');
    }
};
