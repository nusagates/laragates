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
        Schema::create('chat_session_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_session_id');
            $table->unsignedBigInteger('agent_id')->index('chat_session_participants_agent_id_foreign');
            $table->enum('role', ['primary', 'assist'])->default('assist');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['chat_session_id', 'agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_session_participants');
    }
};
