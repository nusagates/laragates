<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {

            if (!Schema::hasColumn('whatsapp_templates', 'remote_id')) {
                $table->string('remote_id')->nullable()->index();
            }

            if (!Schema::hasColumn('whatsapp_templates', 'header_type')) {
                $table->string('header_type')
                      ->nullable()
                      ->comment('text,image,document,none');
            }

            if (!Schema::hasColumn('whatsapp_templates', 'body_params_count')) {
                $table->unsignedTinyInteger('body_params_count')->default(0);
            }

            if (!Schema::hasColumn('whatsapp_templates', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }

            if (!Schema::hasColumn('whatsapp_templates', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable();
            }

            if (!Schema::hasColumn('whatsapp_templates', 'meta')) {
                $table->json('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_templates', 'remote_id')) {
                $table->dropColumn('remote_id');
            }
            if (Schema::hasColumn('whatsapp_templates', 'header_type')) {
                $table->dropColumn('header_type');
            }
            if (Schema::hasColumn('whatsapp_templates', 'body_params_count')) {
                $table->dropColumn('body_params_count');
            }
            if (Schema::hasColumn('whatsapp_templates', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            if (Schema::hasColumn('whatsapp_templates', 'last_sent_at')) {
                $table->dropColumn('last_sent_at');
            }
            if (Schema::hasColumn('whatsapp_templates', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
