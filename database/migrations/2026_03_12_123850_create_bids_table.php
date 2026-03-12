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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->string('bid_ntce_no');
            $table->string('bid_ntce_ord')->default('0');
            $table->string('title');
            $table->string('institution')->nullable();
            $table->string('category');
            $table->string('classification_code')->nullable();
            $table->unsignedBigInteger('budget')->nullable();
            $table->timestamp('bid_open_dt')->nullable();
            $table->timestamp('bid_close_dt')->nullable();
            $table->string('region')->nullable();
            $table->string('success_method')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('status')->default('open');
            $table->string('pipeline_status')->default('discovered');
            $table->timestamps();

            $table->unique(['bid_ntce_no', 'bid_ntce_ord']);
            $table->index(['status', 'bid_close_dt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
