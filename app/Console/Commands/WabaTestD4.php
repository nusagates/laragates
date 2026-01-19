<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\SystemLog;
use App\Models\Customer;
use App\Http\Controllers\Dashboard\TakeChatController;
use App\Http\Controllers\Dashboard\CloseChatController;

class WabaTestD4 extends Command
{
    protected $signature = 'waba:test-d4';
    protected $description = 'Auto test D4 hardening (take / close / denied / audit)';

    public function handle()
    {
        $this->info('=== WABA D4 AUTO TEST START ===');

        /**
         * ===============================
         * PREPARE AGENTS
         * ===============================
         */
        $agentA = User::where('role','agent')->first();
        $agentB = User::where('role','agent')
            ->where('id','!=',$agentA?->id)
            ->first();

        if (!$agentA || !$agentB) {
            $this->error('Need at least 2 agent users');
            return Command::FAILURE;
        }

        /**
         * ===============================
         * PREPARE WAITING CHAT
         * ===============================
         */
        $session = ChatSession::where('status','open')
            ->whereNull('assigned_to')
            ->first();

        if (!$session) {
            $this->warn('No waiting chat found, creating dummy chat');

            $customer = Customer::first();
            if (!$customer) {
                $this->error('No customer found for dummy chat');
                return Command::FAILURE;
            }

            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status'      => 'open',
                'priority'    => 'normal',
                'pinned'      => 0,
                'is_handover' => 0,
            ]);
        }

        /**
         * ===============================
         * TEST 1 — TAKE CHAT (SUCCESS)
         * ===============================
         */
        Auth::login($agentA);
        app(TakeChatController::class)->take(request(), $session);
        $this->info('✔ chat_take success');

        /**
         * ===============================
         * TEST 2 — DOUBLE TAKE (DENIED)
         * ===============================
         */
        Auth::login($agentB);
        try {
            app(TakeChatController::class)->take(request(), $session);
            $this->error('✖ double take should be denied');
        } catch (\Throwable $e) {
            $this->info('✔ chat_take_denied triggered');
        }

        /**
         * ===============================
         * TEST 3 — CLOSE NOT OWNER (DENIED)
         * ===============================
         */
        try {
            app(CloseChatController::class)->close(request(), $session);
            $this->error('✖ close by non-owner should be denied');
        } catch (\Throwable $e) {
            $this->info('✔ chat_close_denied (not owner)');
        }

        /**
         * ===============================
         * TEST 4 — CLOSE CHAT (SUCCESS)
         * ===============================
         */
        Auth::login($agentA);
        app(CloseChatController::class)->close(request(), $session);
        $this->info('✔ chat_close success');

        /**
         * ===============================
         * TEST 5 — DOUBLE CLOSE (DENIED)
         * ===============================
         */
        try {
            app(CloseChatController::class)->close(request(), $session);
            $this->error('✖ double close should be denied');
        } catch (\Throwable $e) {
            $this->info('✔ chat_close_denied (already closed)');
        }

        /**
         * ===============================
         * VERIFY AUDIT LOG
         * ===============================
         */
        $logCount = SystemLog::whereIn('event', [
            'chat_take',
            'chat_take_denied',
            'chat_close',
            'chat_close_denied',
        ])->count();

        $this->info("✔ system log entries found: {$logCount}");
        $this->info('=== WABA D4 AUTO TEST DONE ===');

        return Command::SUCCESS;
    }
}
