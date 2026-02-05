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
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_session_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('subject');
            $table->enum('status', ['pending', 'ongoing', 'closed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('channel', ['whatsapp', 'email', 'phone', 'other'])->default('whatsapp');
            $table->unsignedBigInteger('assigned_to')->nullable()->index('tickets_assigned_to_foreign');
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('sla_warning_sent')->default(false);
            $table->boolean('sla_breached')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
