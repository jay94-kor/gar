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
        Schema::create('company_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('credit_grade')->nullable();
            $table->unsignedBigInteger('total_performance_amount')->nullable();
            $table->boolean('has_iso')->default(false);
            $table->boolean('has_maintenance_network')->default(false);
            $table->json('certifications')->nullable();
            $table->json('penalty_history')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_credentials');
    }
};
