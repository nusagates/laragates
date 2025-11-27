<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_sessions','priority')) {
                $table->enum('priority',['vip','normal','low'])->default('normal')->after('pinned');
            }

            if (!Schema::hasColumn('chat_sessions','closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('last_agent_read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('chat_sessions','priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('chat_sessions','closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
};
