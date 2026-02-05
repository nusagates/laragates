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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions_archive');
    }
};
