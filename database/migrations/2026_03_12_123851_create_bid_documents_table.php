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
        Schema::create('bid_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seq');
            $table->string('filename');
            $table->text('url');
            $table->string('file_type')->nullable();
            $table->string('file_path')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->string('status')->default('queued');
            $table->unsignedTinyInteger('download_attempts')->default(0);
            $table->unsignedTinyInteger('parse_attempts')->default(0);
            $table->timestamps();

            $table->unique(['bid_id', 'seq']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_documents');
    }
};
