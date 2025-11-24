<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->nullable();
            $table->string('language')->default('id');
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');

            $table->text('header')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();

            $table->string('meta_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_templates');
    }
}
