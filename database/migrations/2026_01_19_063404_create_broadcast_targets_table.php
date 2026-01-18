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
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('broadcast_campaign_id')->index('broadcast_targets_broadcast_campaign_id_foreign');
            $table->string('phone')->index();
            $table->string('name')->nullable();
            $table->json('variables')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'queued'])->default('pending');
            $table->string('wa_message_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->json('response_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_targets');
    }
};
