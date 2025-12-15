<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_campaign_id')
                  ->constrained('broadcast_campaigns')
                  ->cascadeOnDelete();

            $table->string('phone'); // nomor tujuan dalam format internasional (62...)
            $table->json('variables')->nullable(); // kalau mau kirim template dengan variable

            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('wa_message_id')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_targets');
    }
};
