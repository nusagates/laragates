<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_behavior_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->nullable()
                  ->index()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('role')->nullable();
            $table->string('action'); // LOGIN, LOGOUT, REQUEST, CREATE_USER, etc
            $table->string('endpoint')->nullable();
            $table->string('method', 10)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_behavior_logs');
    }
};
