<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('category');
            $table->string('language');

            $table->string('status')->default('draft'); // draft / submitted / approved / rejected

            $table->text('header')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();

            // Workflow
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('workflow_notes')->nullable();

            // Meta Fields
            $table->string('meta_id')->nullable();
            $table->string('remote_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
