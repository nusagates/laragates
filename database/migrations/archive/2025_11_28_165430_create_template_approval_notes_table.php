<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('template_approval_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note');
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('whatsapp_templates')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('template_approval_notes');
    }
};
