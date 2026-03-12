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
        Schema::create('bid_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('vehicle_condition')->default('unspecified');
            $table->unsignedSmallInteger('year_threshold')->nullable();
            $table->boolean('registration_requirement')->nullable();
            $table->string('funding_implication')->default('unknown');
            $table->unsignedInteger('contract_months')->nullable();
            $table->decimal('prepayment_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('prepayment_amount')->nullable();
            $table->unsignedBigInteger('deposit')->nullable();
            $table->unsignedInteger('annual_mileage')->nullable();
            $table->decimal('residual_value_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('opening_fee')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('delivery_deadline')->nullable();
            $table->string('delivery_location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_contracts');
    }
};
