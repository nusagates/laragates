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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->enum('role', ['agent', 'admin', 'superadmin', 'supervisor'])->default('agent');
            $table->enum('status', ['online', 'offline', 'pending'])->default('pending');
            $table->boolean('is_online')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->json('skills')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
