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
        Schema::create('company_fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model');
            $table->string('trim')->nullable();
            $table->string('fuel_type')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedTinyInteger('seats')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_fleets');
    }
};
