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
        Schema::create('broadcast_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index();
            $table->unsignedBigInteger('whatsapp_template_id')->nullable()->index('broadcast_campaigns_whatsapp_template_id_foreign');
            $table->enum('audience_type', ['all', 'upload'])->default('upload');
            $table->unsignedInteger('total_targets')->default(0);
            $table->boolean('send_now')->default(false);
            $table->timestamp('send_at')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'scheduled', 'running', 'done', 'failed'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable()->index('broadcast_campaigns_created_by_foreign');
            $table->unsignedBigInteger('approved_by')->nullable()->index('broadcast_campaigns_approved_by_foreign');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_campaigns');
    }
};
