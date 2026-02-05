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
        Schema::create('template_approval_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('template_id')->index('template_approval_notes_template_id_foreign');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_approval_notes');
    }
};
