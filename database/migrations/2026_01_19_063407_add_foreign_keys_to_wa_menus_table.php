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
        Schema::table('wa_menus', function (Blueprint $table) {
            $table->foreign(['parent_id'])->references(['id'])->on('wa_menus')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_menus', function (Blueprint $table) {
            $table->dropForeign('wa_menus_parent_id_foreign');
        });
    }
};
