<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions_archive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->boolean('pinned')->default(false);
            $table->enum('priority', ['normal', 'high', 'vip'])->default('normal');
            $table->enum('status', ['bot', 'pending', 'open', 'closed'])->default('closed');
            $table->boolean('is_handover')->default(false);
            $table->string('bot_state')->nullable();
            $table->string('bot_context')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at');
            $table->timestamp('last_agent_read_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->integer('first_response_seconds')->nullable();
            $table->integer('resolution_seconds')->nullable();
            $table->string('sla_status', 20)->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'archived_at']);
            $table->index('assigned_to');
        });

        Schema::create('chat_messages_archive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_archive_id')->constrained('chat_sessions_archive')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('sender', ['customer', 'agent', 'system'])->default('customer');
            $table->text('message');
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document'])->default('text');
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->boolean('is_outgoing')->default(false);
            $table->boolean('is_internal')->default(false);
            $table->json('reactions')->nullable();
            $table->timestamps();

            $table->index(['chat_session_archive_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages_archive');
        Schema::dropIfExists('chat_sessions_archive');
    }
};
