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
        Schema::create('broadcast_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('broadcast_campaign_id')->index('broadcast_approvals_broadcast_campaign_id_foreign');
            $table->unsignedBigInteger('requested_by')->nullable()->index('broadcast_approvals_requested_by_foreign');
            $table->text('request_notes')->nullable();
            $table->enum('action', ['requested', 'approved', 'rejected', 'revised'])->default('requested');
            $table->unsignedBigInteger('acted_by')->nullable()->index('broadcast_approvals_acted_by_foreign');
            $table->text('action_notes')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_approvals');
    }
};
