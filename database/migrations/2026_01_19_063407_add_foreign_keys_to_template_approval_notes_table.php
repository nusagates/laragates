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
        Schema::table('template_approval_notes', function (Blueprint $table) {
            $table->foreign(['template_id'])->references(['id'])->on('whatsapp_templates')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_approval_notes', function (Blueprint $table) {
            $table->dropForeign('template_approval_notes_template_id_foreign');
        });
    }
};
