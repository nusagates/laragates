<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_session_id')
                  ->nullable()
                  ->after('id');

            $table->index('chat_session_id', 'tickets_chat_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_chat_session_id_index');
            $table->dropColumn('chat_session_id');
        });
    }
};
