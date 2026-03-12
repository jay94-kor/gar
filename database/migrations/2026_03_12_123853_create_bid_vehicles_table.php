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
        Schema::create('bid_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seq')->default(1);
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('trim')->nullable();
            $table->string('fuel_type')->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('year_condition')->nullable();
            $table->string('color_exterior')->nullable();
            $table->string('color_interior')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['bid_id', 'seq']);
            $table->index(['bid_id', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_vehicles');
    }
};
