<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->boolean('pinned')->default(false)->after('assigned_to');
            $table->enum('priority', ['normal','high','vip'])
                  ->default('normal')->after('pinned');

            $table->timestamp('last_agent_read_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['pinned','priority','last_agent_read_at']);
        });
    }
};
