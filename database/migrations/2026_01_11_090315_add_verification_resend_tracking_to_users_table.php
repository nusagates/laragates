<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('verification_resend_count')->default(0)->after('email_verify_grace_until');
            $table->timestamp('last_verification_sent_at')->nullable()->after('verification_resend_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_resend_count',
                'last_verification_sent_at',
            ]);
        });
    }
};
