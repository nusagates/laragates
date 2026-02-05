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
        Schema::table('broadcast_approvals', function (Blueprint $table) {
            $table->foreign(['acted_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['broadcast_campaign_id'])->references(['id'])->on('broadcast_campaigns')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['requested_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_approvals', function (Blueprint $table) {
            $table->dropForeign('broadcast_approvals_acted_by_foreign');
            $table->dropForeign('broadcast_approvals_broadcast_campaign_id_foreign');
            $table->dropForeign('broadcast_approvals_requested_by_foreign');
        });
    }
};
