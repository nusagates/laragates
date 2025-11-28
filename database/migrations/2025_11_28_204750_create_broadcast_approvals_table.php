<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastApprovalsTable extends Migration
{
    public function up()
    {
        Schema::create('broadcast_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_campaign_id')
                  ->constrained('broadcast_campaigns')
                  ->cascadeOnDelete();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('request_notes')->nullable();

            $table->enum('action', ['requested', 'approved', 'rejected', 'revised'])->default('requested');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_notes')->nullable();

            $table->timestamp('acted_at')->nullable();

            // maintain historical snapshot of campaign at time of request (optional)
            $table->json('snapshot')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_approvals');
    }
}
