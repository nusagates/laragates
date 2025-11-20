<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Campaign name
            $table->foreignId('whatsapp_template_id')->constrained()->cascadeOnDelete(); // which template used
            $table->enum('audience_type', ['all', 'segment', 'csv']); // target type
            $table->json('audience_data')->nullable(); // segment rules or CSV list
            $table->enum('status', ['pending', 'scheduled', 'processing', 'completed', 'failed'])
                ->default('pending');
            $table->timestamp('scheduled_at')->nullable(); // for schedule later
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('sent_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
