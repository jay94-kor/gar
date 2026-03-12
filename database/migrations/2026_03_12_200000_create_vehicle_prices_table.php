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
        Schema::create('vehicle_prices', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer');
            $table->string('model');
            $table->string('trim');
            $table->string('fuel_type');
            $table->unsignedSmallInteger('model_year');
            $table->unsignedBigInteger('new_price')->nullable();
            $table->unsignedBigInteger('registration_cost')->nullable();
            $table->unsignedBigInteger('resale_12m')->nullable();
            $table->unsignedBigInteger('resale_24m')->nullable();
            $table->unsignedBigInteger('resale_36m')->nullable();
            $table->unsignedBigInteger('resale_48m')->nullable();
            $table->unsignedBigInteger('insurance_annual')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->unique(['manufacturer', 'model', 'trim', 'fuel_type', 'model_year'], 'vehicle_prices_lookup_unique');
            $table->index(['model', 'model_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_prices');
    }
};
