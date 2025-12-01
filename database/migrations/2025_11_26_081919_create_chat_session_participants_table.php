<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();

            $table->enum('role', ['primary','assist'])->default('assist');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();
            $table->unique(['chat_session_id','agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_session_participants');
    }
};
