<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {

            // Tambahkan hanya jika belum ada
            if (!Schema::hasColumn('chat_messages', 'media_url')) {
                $table->string('media_url')->nullable()->after('message');
            }

            if (!Schema::hasColumn('chat_messages', 'media_type')) {
                $table->string('media_type')->nullable()->after('media_url');
            }

            if (!Schema::hasColumn('chat_messages', 'file_name')) {
                $table->string('file_name')->nullable()->after('media_type');
            }

            if (!Schema::hasColumn('chat_messages', 'file_size')) {
                $table->bigInteger('file_size')->nullable()->after('file_name');
            }

            if (!Schema::hasColumn('chat_messages', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_size');
            }
        });
    }

    public function down(): void
    {
        // Jangan hapus kolom supaya aman (beda deployment/production)
        Schema::table('chat_messages', function (Blueprint $table) {
            // kosong, biarkan aman
        });
    }
};
