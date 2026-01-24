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
        Schema::table('broadcast_targets', function (Blueprint $table) {
            $table->string('state_id')->nullable()->after('wa_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_targets', function (Blueprint $table) {
            $table->dropColumn('state_id');
        });
    }
};
