<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable();
            $table->integer('first_response_seconds')->nullable();
            $table->integer('resolution_seconds')->nullable();
            $table->string('sla_status', 20)->nullable();
            // meet | breach
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'first_response_at',
                'first_response_seconds',
                'resolution_seconds',
                'sla_status',
            ]);
        });
    }
};
