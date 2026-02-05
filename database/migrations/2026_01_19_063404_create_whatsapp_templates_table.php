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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('category');
            $table->string('language');
            $table->string('status')->default('draft');
            $table->text('header')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('workflow_notes')->nullable();
            $table->string('meta_id')->nullable();
            $table->string('remote_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->string('header_type')->nullable()->comment('text,image,document,none');
            $table->unsignedTinyInteger('body_params_count')->default(0);
            $table->json('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
