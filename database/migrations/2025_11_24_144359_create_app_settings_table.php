<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable()->default('CS WABA Demo');
            $table->string('timezone')->nullable()->default('Asia/Jakarta');

            $table->string('wa_phone')->nullable();
            $table->string('wa_webhook')->nullable();
            $table->string('wa_api_key')->nullable();

            $table->boolean('notif_sound')->default(false);
            $table->boolean('notif_desktop')->default(false);
            $table->boolean('auto_assign_ticket')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
