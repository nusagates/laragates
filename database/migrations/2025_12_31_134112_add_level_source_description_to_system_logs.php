<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->string('source', 20)->default('SYSTEM')->after('id');
            $table->string('level', 20)->default('info')->after('event');
            $table->string('description')->nullable()->after('event');
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropColumn(['source', 'level', 'description']);
        });
    }
};
