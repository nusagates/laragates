<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_targets', 'status')) {
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->after('variables');
            }

            if (!Schema::hasColumn('broadcast_targets', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('broadcast_targets', 'error_message')) {
                $table->text('error_message')->nullable()->after('sent_at');
            }

            if (!Schema::hasColumn('broadcast_targets', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0)->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_targets', function (Blueprint $table) {
            if (Schema::hasColumn('broadcast_targets', 'attempts')) {
                $table->dropColumn('attempts');
            }
            if (Schema::hasColumn('broadcast_targets', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('broadcast_targets', 'sent_at')) {
                $table->dropColumn('sent_at');
            }
            if (Schema::hasColumn('broadcast_targets', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
