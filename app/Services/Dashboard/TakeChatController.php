<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TakeChatController extends Controller
{
    public function take(Request $request, ChatSession $session)
    {
        DB::transaction(function () use ($session, $request) {

            // 🔒 Lock row (anti double take)
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->assigned_to) {
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
             * SYSTEM LOG (D.3)
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
