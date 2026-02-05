<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->text('summary_text');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_summaries');
    }
};
