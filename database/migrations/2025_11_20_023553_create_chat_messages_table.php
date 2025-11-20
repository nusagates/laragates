<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->enum('sender', ['customer', 'agent']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // kalau agent
            $table->text('message')->nullable();
            $table->enum('type', ['text', 'image', 'file', 'template'])->default('text');
            $table->string('wa_message_id')->nullable();
            $table->json('meta')->nullable(); // simpan data tambahan (file url, dsb)
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};

