<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkflowAndLastSentToWhatsappTemplatesTable extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_templates', 'workflow_notes')) {
                $table->text('workflow_notes')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('whatsapp_templates', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable()->after('last_synced_at');
            }
        });
    }

    public function down()
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['workflow_notes', 'last_sent_at']);
        });
    }
}
