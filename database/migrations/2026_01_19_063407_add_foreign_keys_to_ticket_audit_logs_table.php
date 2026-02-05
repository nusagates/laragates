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
        Schema::table('ticket_audit_logs', function (Blueprint $table) {
            $table->foreign(['ticket_id'])->references(['id'])->on('tickets')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_audit_logs', function (Blueprint $table) {
            $table->dropForeign('ticket_audit_logs_ticket_id_foreign');
            $table->dropForeign('ticket_audit_logs_user_id_foreign');
        });
    }
};
