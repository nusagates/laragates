<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatMessageArchive;
use App\Models\ChatSession;
use App\Models\ChatSessionArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatArchiveService
{
    /**
     * Archive sessions that have been closed for more than 7 days
     */
    public function archiveOldClosedSessions(): int
    {
        $cutoffDate = Carbon::now()->subDays(7);
        $archivedCount = 0;

        // Find closed sessions older than 7 days using closed_at timestamp
        $sessionsToArchive = ChatSession::where('status', 'closed')
            ->where('closed_at', '<=', $cutoffDate)
            ->whereNotNull('closed_at')
            ->get();

        foreach ($sessionsToArchive as $session) {
            $messageCount = ChatMessage::where('chat_session_id', $session->id)->count();

            try {
                DB::transaction(function () use ($session) {
                    // 1. Copy session to archive table
                    $archivedSession = ChatSessionArchive::create([
                        'customer_id' => $session->customer_id,
                        'assigned_to' => $session->assigned_to,
                        'pinned' => $session->pinned ?? false,
                        'priority' => $session->priority ?? 'normal',
                        'status' => $session->status,
                        'is_handover' => $session->is_handover ?? false,
                        'bot_state' => $session->bot_state,
                        'bot_context' => $session->bot_context,
                        'closed_at' => $session->closed_at,
                        'archived_at' => now(),
                        'last_agent_read_at' => $session->last_agent_read_at,
                        'first_response_at' => $session->first_response_at,
                        'first_response_seconds' => $session->first_response_seconds,
                        'resolution_seconds' => $session->resolution_seconds,
                        'sla_status' => $session->sla_status,
                        'created_at' => $session->created_at,
                        'updated_at' => $session->updated_at,
                    ]);

                    // 2. Copy all messages to archive table
                    $messages = ChatMessage::where('chat_session_id', $session->id)->get();

                    foreach ($messages as $message) {
                        ChatMessageArchive::create([
                            'chat_session_archive_id' => $archivedSession->id,
                            'customer_id' => $message->customer_id,
                            'sender' => $message->sender,
                            'message' => $message->message,
                            'type' => $message->type,
                            'media_url' => $message->media_url,
                            'media_type' => $message->media_type,
                            'delivery_status' => $message->delivery_status,
                            'is_outgoing' => $message->is_outgoing,
                            'is_internal' => $message->is_internal,
                            'reactions' => $message->reactions,
                            'created_at' => $message->created_at,
                            'updated_at' => $message->updated_at,
                        ]);
                    }

                    // 3. Delete messages from original table
                    ChatMessage::where('chat_session_id', $session->id)->delete();

                    // 4. Delete session from original table
                    $session->delete();
                });

                $archivedCount++;

                Log::info("Archived session {$session->id}", [
                    'customer_id' => $session->customer_id,
                    'message_count' => $messageCount,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to archive session {$session->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $archivedCount;
    }

    /**
     * Get archived sessions for a customer
     */
    public function getCustomerArchive(int $customerId)
    {
        return ChatSessionArchive::where('customer_id', $customerId)
            ->with(['agent', 'messages'])
            ->orderBy('archived_at', 'desc')
            ->get();
    }

    /**
     * Get archived session details with messages
     */
    public function getArchivedSession(int $archiveId)
    {
        return ChatSessionArchive::with(['customer', 'agent', 'messages'])
            ->findOrFail($archiveId);
    }
}
