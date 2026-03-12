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
        Schema::table('bid_documents', function (Blueprint $table) {
            $table->string('content_type')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('content_type');
            $table->text('download_error')->nullable()->after('status');
            $table->timestamp('downloaded_at')->nullable()->after('download_error');
            $table->timestamp('parsed_at')->nullable()->after('downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bid_documents', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'file_size',
                'download_error',
                'downloaded_at',
                'parsed_at',
            ]);
        });
    }
};
