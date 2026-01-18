<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('iam_audit_logs', function (Blueprint $table) {
            $table->id();

            // siapa yang melakukan aksi
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // user yang terdampak
            $table->foreignId('subject_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // CREATE / APPROVE / ROLE_UPDATE / DELETE / REVOKE
            $table->string('action', 50);

            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['subject_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iam_audit_logs');
    }
};