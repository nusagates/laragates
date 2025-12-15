<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
<<<<<<< HEAD
=======
=======
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','skills')) {
                $table->json('skills')->nullable()->after('idle_timeout_minutes');
            }

            if (!Schema::hasColumn('users','is_active')) {
                $table->boolean('is_active')->default(true)->after('skills');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','skills')) {
                $table->dropColumn('skills');
            }
            if (Schema::hasColumn('users','is_active')) {
                $table->dropColumn('is_active');
            }
        });
>>>>>>> production
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    }
};
