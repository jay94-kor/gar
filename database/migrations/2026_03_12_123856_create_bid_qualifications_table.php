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
        Schema::create('bid_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('biz_registration')->nullable();
            $table->string('region_limit')->nullable();
            $table->string('company_size_limit')->nullable();
            $table->boolean('joint_contract')->nullable();
            $table->boolean('subcontract')->nullable();
            $table->string('branch_requirement')->nullable();
            $table->json('other_requirements')->nullable();
            $table->string('evaluation_method')->nullable();
            $table->string('evaluation_standard')->nullable();
            $table->decimal('success_threshold', 5, 2)->nullable();
            $table->unsignedTinyInteger('passing_score')->nullable();
            $table->string('price_basis')->nullable();
            $table->unsignedTinyInteger('preliminary_prices_count')->nullable();
            $table->string('preliminary_prices_range')->nullable();
            $table->unsignedTinyInteger('score_performance')->nullable();
            $table->unsignedTinyInteger('score_financial')->nullable();
            $table->unsignedTinyInteger('score_afterservice')->nullable();
            $table->unsignedTinyInteger('score_price')->nullable();
            $table->decimal('score_credibility_plus', 4, 2)->nullable();
            $table->decimal('score_credibility_minus', 4, 2)->nullable();
            $table->integer('score_disqualify')->nullable();
            $table->boolean('score_adjusted')->default(false);
            $table->json('required_docs')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_qualifications');
    }
};
