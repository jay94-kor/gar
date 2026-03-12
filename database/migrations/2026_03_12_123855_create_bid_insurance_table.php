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
        Schema::create('bid_insurance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('liability_1')->nullable();
            $table->string('liability_2')->nullable();
            $table->unsignedBigInteger('property_damage')->nullable();
            $table->boolean('own_vehicle')->nullable();
            $table->unsignedInteger('own_vehicle_deductible')->nullable();
            $table->string('personal_injury')->nullable();
            $table->string('uninsured_motorist')->nullable();
            $table->unsignedTinyInteger('driver_age_min')->nullable();
            $table->string('driver_scope')->nullable();
            $table->string('emergency_service')->nullable();
            $table->string('special_coverage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_insurance');
    }
};
