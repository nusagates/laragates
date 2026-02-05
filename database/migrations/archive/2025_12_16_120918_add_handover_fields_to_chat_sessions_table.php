<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {

            if (!Schema::hasColumn('chat_sessions', 'is_handover')) {
                $table->boolean('is_handover')
                      ->default(false)
                      ->after('status');
            }

            if (!Schema::hasColumn('chat_sessions', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')
                      ->nullable()
                      ->after('is_handover');
            }

            if (!Schema::hasColumn('chat_sessions', 'bot_state')) {
                $table->string('bot_state')
                      ->nullable()
                      ->after('assigned_to');
            }

            if (!Schema::hasColumn('chat_sessions', 'bot_context')) {
                $table->string('bot_context')
                      ->nullable()
                      ->after('bot_state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {

            if (Schema::hasColumn('chat_sessions', 'is_handover')) {
                $table->dropColumn('is_handover');
            }

            if (Schema::hasColumn('chat_sessions', 'assigned_to')) {
                $table->dropColumn('assigned_to');
            }

            if (Schema::hasColumn('chat_sessions', 'bot_state')) {
                $table->dropColumn('bot_state');
            }

            if (Schema::hasColumn('chat_sessions', 'bot_context')) {
                $table->dropColumn('bot_context');
            }
        });
    }
};
