<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('broadcast_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama campaign
            $table->foreignId('whatsapp_template_id')->nullable()
                ->constrained('whatsapp_templates')
                ->nullOnDelete();

            $table->enum('audience_type', ['all', 'csv'])->default('csv');
            $table->unsignedInteger('total_targets')->default(0);

            $table->enum('status', ['pending', 'scheduled', 'running', 'finished', 'failed'])
                  ->default('pending');

            // schedule
            $table->boolean('send_now')->default(true);
            $table->timestamp('send_at')->nullable();

            // ringkasan hasil
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_campaigns');
    }
};
