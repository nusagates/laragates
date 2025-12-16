<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'skills')) {
                $table->json('skills')
                    ->nullable()
                    ->after('idle_timeout_minutes');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('skills');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'skills')) {
                $table->dropColumn('skills');
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }

        });
    }
};
