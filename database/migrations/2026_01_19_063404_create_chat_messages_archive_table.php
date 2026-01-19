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
        Schema::create('chat_messages_archive', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_session_archive_id');
            $table->unsignedBigInteger('customer_id')->index('chat_messages_archive_customer_id_foreign');
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
    }
};
