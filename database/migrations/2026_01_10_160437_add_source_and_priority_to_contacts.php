<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source')->nullable()->after('phone');
            $table->enum('priority', ['low', 'normal', 'high'])
                  ->default('normal')
                  ->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['source', 'priority']);
        });
    }
};
