<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastTargetsTable extends Migration
{
    public function up()
    {
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_campaign_id')
                  ->constrained('broadcast_campaigns')
                  ->cascadeOnDelete();

            $table->string('phone')->index();
            $table->string('name')->nullable();

            // variables to inject into template (JSON)
            $table->json('variables')->nullable();

            // WABA response tracking
            $table->enum('status', ['pending', 'sent', 'failed', 'queued'])->default('pending');
            $table->string('wa_message_id')->nullable()->index();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->json('response_log')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_targets');
    }
}
