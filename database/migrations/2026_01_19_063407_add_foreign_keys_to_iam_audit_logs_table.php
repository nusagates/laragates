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
        Schema::table('iam_audit_logs', function (Blueprint $table) {
            $table->foreign(['actor_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['subject_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iam_audit_logs', function (Blueprint $table) {
            $table->dropForeign('iam_audit_logs_actor_id_foreign');
            $table->dropForeign('iam_audit_logs_subject_id_foreign');
        });
    }
};
