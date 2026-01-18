<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use App\Models\User;
use App\Services\ChatAgentRouter;
use Illuminate\Console\Command;

class ReassignOfflineAgentSessions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agent:reassign-offline-sessions';

    /**
     * The console command description.
     */
    protected $description = 'Reassign chat sessions from offline agents to available online agents';

    public function __construct(
        private readonly ChatAgentRouter $agentRouter
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for sessions assigned to offline agents...');

        $offlineThreshold = now()->subMinutes(5);

        User::where('role', 'agent')
            ->where(function ($query) use ($offlineThreshold) {
                $query->where('last_seen', '<', $offlineThreshold)
                    ->orWhere('is_online', false)
                    ->orWhere('status', 'offline');
            })
            ->each(function (User $agent) {
                $this->markAgentOffline($agent);
            });

        $sessionsReassigned = 0;

        ChatSession::whereHas('agent', function ($query) use ($offlineThreshold) {
            $query->where(function ($q) use ($offlineThreshold) {
                $q->where('last_seen', '<', $offlineThreshold)
                    ->orWhere('is_online', false)
                    ->orWhere('status', 'offline');
            });
        })
            ->whereIn('status', ['open', 'pending'])
            ->each(function (ChatSession $session) use (&$sessionsReassigned) {
                $this->info("Reassigning session {$session->id} from offline agent...");

                $this->agentRouter->assignSession($session);

                if ($session->assigned_to) {
                    $sessionsReassigned++;
                    $this->info("Session {$session->id} reassigned to agent {$session->assigned_to}");
                } else {
                    $this->warn("Session {$session->id} moved to pending queue (no available agents)");
                }
            });

        $this->info("Reassigned {$sessionsReassigned} sessions from offline agents.");

        return Command::SUCCESS;
    }

    private function markAgentOffline(User $agent): void
    {
        $agent->update([
            'is_online' => false,
            'status' => 'offline',
        ]);

        $this->info("Marked agent {$agent->name} (ID: {$agent->id}) as offline");
    }
}
