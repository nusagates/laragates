<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('ticket_sla_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                  ->constrained('tickets')
                  ->cascadeOnDelete();

            // SLA rule identifier
            $table->string('rule'); 
            // contoh:
            // pending_to_ongoing
            // ongoing_to_closed

            // SLA result
            $table->enum('status', ['ok', 'warning', 'breach']);

            // kapan SLA ter-trigger
            $table->timestamp('triggered_at');

            // data tambahan (menit, priority, dll)
            $table->json('meta')->nullable();

            $table->timestamps();

            // index untuk query cepat
            $table->index(['ticket_id', 'rule']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sla_logs');
    }
};
