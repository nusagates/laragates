<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('broadcast_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('whatsapp_template_id')
                  ->nullable()
                  ->constrained('whatsapp_templates')
                  ->nullOnDelete();

            $table->enum('audience_type', ['all', 'upload'])->default('upload');
            $table->unsignedInteger('total_targets')->default(0);

            // Scheduler
            $table->boolean('send_now')->default(false);
            $table->timestamp('send_at')->nullable();

            // workflow/status
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'scheduled',
                'running',
                'done',
                'failed'
            ])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // reporting
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            // meta info
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_campaigns');
    }
}
