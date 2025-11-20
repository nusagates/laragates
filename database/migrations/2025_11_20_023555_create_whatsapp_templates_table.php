<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Order_Confirmation
            $table->string('category');       // utility / marketing / authentication
            $table->string('language');       // id / en / dsb
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->json('structure');        // body, header, footer, buttons
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};

