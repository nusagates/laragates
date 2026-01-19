<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\SystemLog;
use App\Services\Security\RateMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TakeChatController extends Controller
{
    public function take(Request $request, ChatSession $session)
    {
        /**
         * ===============================
         * D5.1 — RATE MONITOR (MONITOR ONLY)
         * ===============================
         */
        RateMonitorService::check('chat_take', 8);

        DB::transaction(function () use ($session, $request) {

            // 🔒 Lock row (anti race condition)
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->firstOrFail();

            /**
             * ===============================
             * DENIED: ALREADY TAKEN
             * ===============================
             */
            if ($session->assigned_to) {
                SystemLog::create([
                    'event'       => 'chat_take_denied',
                    'entity_type' => 'chat_session',
                    'entity_id'   => $session->id,
                    'user_id'     => Auth::id(),
                    'user_role'   => Auth::user()->role,
                    'ip_address'  => $request->ip(),
                    'meta'        => json_encode([
                        'reason'      => 'already_taken',
                        'assigned_to' => $session->assigned_to,
                    ]),
                ]);

                abort(409, 'Chat already taken');
            }

            /**
             * ===============================
             * BEFORE STATE
             * ===============================
             */
            $oldValues = [
                'assigned_to' => $session->assigned_to,
                'status'      => $session->status,
            ];

            /**
             * ===============================
             * UPDATE
             * ===============================
             */
            $session->update([
                'assigned_to'        => Auth::id(),
                'last_agent_read_at' => now(),
            ]);

            /**
             * ===============================
             * AFTER STATE
             * ===============================
             */
            $newValues = [
                'assigned_to' => $session->assigned_to,
                'status'      => $session->status,
            ];

            /**
             * ===============================
             * SYSTEM LOG (SUCCESS)
             * ===============================
             */
            SystemLog::create([
                'event'       => 'chat_take',
                'entity_type' => 'chat_session',
                'entity_id'   => $session->id,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()->role,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => $request->ip(),
                'meta'        => json_encode([
                    'action' => 'agent_take_chat',
                ]),
            ]);
        });

        return redirect()->route('chat');
    }
}
