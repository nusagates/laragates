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
        Schema::table('chat_session_participants', function (Blueprint $table) {
            $table->foreign(['agent_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['chat_session_id'])->references(['id'])->on('chat_sessions')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_session_participants', function (Blueprint $table) {
            $table->dropForeign('chat_session_participants_agent_id_foreign');
            $table->dropForeign('chat_session_participants_chat_session_id_foreign');
        });
    }
};
