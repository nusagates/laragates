<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();

            // ===============================
            // KONTEKS PENGGUNA & SESSION
            // ===============================
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_session_id')->nullable();

            // ===============================
            // AI METADATA
            // ===============================
            $table->string('action')->nullable(); 
            // contoh: chat_summary, auto_reply, classify

            $table->string('model')->nullable();  
            // contoh: dummy-ai-v1, gpt-4, dll

            // ===============================
            // SECURITY & STATUS
            // ===============================
            $table->string('prompt_hash', 64); 
            // SHA256 → TIDAK simpan prompt asli (compliance safe)

            $table->enum('response_status', [
                'success',
                'failed',
                'timeout'
            ])->default('success');

            // ===============================
            // PERFORMANCE & ERROR
            // ===============================
            $table->integer('latency_ms')->nullable();
            $table->text('error_message')->nullable();

            // ===============================
            // METADATA TAMBAHAN
            // ===============================
            $table->json('meta')->nullable();
            // contoh: token_estimate, cost_estimate, flags

            $table->timestamps();

            // ===============================
            // INDEX (AUDIT & REPORTING)
            // ===============================
            $table->index('user_id');
            $table->index('chat_session_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};
