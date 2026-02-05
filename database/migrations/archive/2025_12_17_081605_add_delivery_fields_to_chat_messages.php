<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('delivery_status')
                ->default('queued')
                ->after('status');

            $table->unsignedTinyInteger('retry_count')
                ->default(0)
                ->after('delivery_status');

            $table->timestamp('last_retry_at')
                ->nullable()
                ->after('retry_count');

            $table->text('last_error')
                ->nullable()
                ->after('last_retry_at');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_status',
                'retry_count',
                'last_retry_at',
                'last_error',
            ]);
        });
    }
};
