<?php

namespace App\Console\Commands;

use App\Services\ChatArchiveService;
use Illuminate\Console\Command;

class ArchiveClosedSessions extends Command
{
    protected $signature = 'chat:archive-closed-sessions';

    protected $description = 'Archive chat sessions that have been closed for more than 7 days';

    public function __construct(
        protected ChatArchiveService $archiveService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting to archive old closed sessions...');

        $archivedCount = $this->archiveService->archiveOldClosedSessions();

        $this->info("Successfully archived {$archivedCount} session(s).");

        return self::SUCCESS;
    }
}
