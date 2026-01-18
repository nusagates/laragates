<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('template_id');
            $table->string('version_label')->nullable();

            $table->text('header')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();

            $table->json('buttons')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            $table
                ->foreign('template_id')
                ->references('id')
                ->on('whatsapp_templates')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
    }
};
