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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_session_id')->index('chat_messages_chat_session_id_foreign');
            $table->boolean('is_outgoing')->default(false);
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_bot')->default(false);
            $table->json('reactions')->nullable();
            $table->enum('sender', ['customer', 'agent', 'system']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message')->nullable();
            $table->text('media_url')->nullable();
            $table->enum('media_type', ['image', 'file', 'video', 'voice'])->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('type')->default('text');
            $table->string('wa_message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'received', 'read', 'failed'])->default('pending');
            $table->string('delivery_status')->default('queued');
            $table->string('state_id', 100)->nullable()->index();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
