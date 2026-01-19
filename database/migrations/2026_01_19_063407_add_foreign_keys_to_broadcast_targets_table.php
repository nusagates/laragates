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
            $table->foreign(['broadcast_campaign_id'])->references(['id'])->on('broadcast_campaigns')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_targets', function (Blueprint $table) {
            $table->dropForeign('broadcast_targets_broadcast_campaign_id_foreign');
        });
    }
};
