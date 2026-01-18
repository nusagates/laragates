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
        Schema::table('chat_messages_archive', function (Blueprint $table) {
            $table->foreign(['chat_session_archive_id'])->references(['id'])->on('chat_sessions_archive')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['customer_id'])->references(['id'])->on('customers')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages_archive', function (Blueprint $table) {
            $table->dropForeign('chat_messages_archive_chat_session_archive_id_foreign');
            $table->dropForeign('chat_messages_archive_customer_id_foreign');
        });
    }
};
