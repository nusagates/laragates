<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('sla_warning_sent')
                ->default(false)
                ->after('last_message_at');

            $table->boolean('sla_breached')
                ->default(false)
                ->after('sla_warning_sent');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'sla_warning_sent',
                'sla_breached',
            ]);
        });
    }
};
