<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('name');
            $table->text('notes')->nullable()->after('tags');

            $table->boolean('is_blacklisted')->default(false)->after('notes');
            $table->boolean('is_vip')->default(false)->after('is_blacklisted');

            $table->unsignedInteger('total_chats')->default(0)->after('is_vip');
            $table->unsignedInteger('total_messages')->default(0)->after('total_chats');

            $table->timestamp('last_contacted_at')->nullable()->after('total_messages');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'tags',
                'notes',
                'is_blacklisted',
                'is_vip',
                'total_chats',
                'total_messages',
                'last_contacted_at',
            ]);
        });
    }
};
