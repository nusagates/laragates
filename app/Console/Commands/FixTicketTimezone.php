<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTicketTimezone extends Command
{
    protected $signature = 'tickets:fix-timezone';
    protected $description = 'Convert old UTC timestamps to Asia/Jakarta timezone (+07:00) for tickets and ticket_messages';

    public function handle()
    {
        $this->info('=== Fixing timezone for existing ticket records...');

        $tables = [
            'tickets',
            'ticket_messages',
        ];

        foreach ($tables as $table) {
            $this->line("Processing table: $table...");

            $updated = DB::update("
                UPDATE {$table}
                SET created_at = CONVERT_TZ(created_at, '+00:00', '+07:00'),
                    updated_at = CONVERT_TZ(updated_at, '+00:00', '+07:00')
                WHERE created_at IS NOT NULL
            ");

            $this->info("Updated records on {$table}: " . $updated);
        }

        $this->info('=== Timezone fix completed successfully! ===');
        return Command::SUCCESS;
    }
}
