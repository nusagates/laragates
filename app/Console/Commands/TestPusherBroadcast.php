<?php

namespace App\Console\Commands;

use App\Events\Chat\MessageSent;
use App\Models\ChatMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPusherBroadcast extends Command
{
    protected $signature = 'test:pusher';

    protected $description = 'Test Pusher broadcast functionality';

    public function handle()
    {
        $this->info('🔔 Testing Pusher Broadcast...');

        // Get any existing message or create a test scenario
        $message = ChatMessage::latest()->first();

        if (!$message) {
            $this->error('❌ No chat message found in database.');
            $this->info('💡 Send a test webhook first to create a message.');
            return 1;
        }

        $this->info("📨 Broadcasting message ID: {$message->id}");
        $this->info("📍 Session ID: {$message->chat_session_id}");
        $this->info("📢 Channel: chat-session.{$message->chat_session_id}");

        try {
            // Attempt to broadcast
            broadcast(new MessageSent($message))->toOthers();

            $this->info('✅ Broadcast dispatched successfully!');
            $this->newLine();
            $this->info('📋 Check the following:');
            $this->line('  1. BROADCAST_DRIVER in .env = pusher');
            $this->line('  2. Pusher credentials are correct');
            $this->line('  3. Queue worker is running (php artisan queue:work)');
            $this->line('  4. Frontend Echo is listening on the correct channel');

            Log::info('[TEST PUSHER] Broadcast sent', [
                'message_id' => $message->id,
                'session_id' => $message->chat_session_id,
                'channel' => "chat-session.{$message->chat_session_id}",
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Broadcast failed!');
            $this->error($e->getMessage());
            Log::error('[TEST PUSHER] Broadcast failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
