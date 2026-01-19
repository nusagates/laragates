<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->boolean('is_outgoing')->default(false)->after('chat_session_id');
            $table->boolean('is_internal')->default(false)->after('is_outgoing');
            $table->boolean('is_bot')->default(false)->after('is_internal');

            $table->text('media_url')->nullable()->after('message');
            $table->enum('media_type', ['image','file','video','voice'])
                  ->nullable()->after('media_url');

            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])
                  ->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['is_outgoing','is_internal','is_bot','media_url','media_type']);
            $table->enum('status', ['sent'])->default('sent')->change();
        });
    }
};
