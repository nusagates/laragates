<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE chat_sessions MODIFY COLUMN status ENUM('bot', 'pending', 'open', 'closed') DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE chat_sessions MODIFY COLUMN status ENUM('pending', 'open', 'closed') DEFAULT 'open'");
    }
};
