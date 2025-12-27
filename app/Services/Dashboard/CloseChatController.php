<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CloseChatController extends Controller
{
    public function close(Request $request, ChatSession $session)
    {
        DB::transaction(function () use ($session, $request) {

            // 🔒 Lock row (anti double close)
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'closed') {
                abort(409, 'Chat already closed');
            }

            if ($session->assigned_to !== Auth::id()) {
                abort(403, 'Unauthorized');
            }

            /**
             * ===============================
             * BEFORE STATE
             * ===============================
             */
            $oldValues = [
                'status'    => $session->status,
                'closed_at' => $session->closed_at,
            ];

            /**
             * ===============================
             * UPDATE
             * ===============================
             */
            $session->update([
                'status'    => 'closed',
                'closed_at'=> now(),
            ]);

            /**
             * ===============================
             * AFTER STATE
             * ===============================
             */
            $newValues = [
                'status'    => $session->status,
                'closed_at'=> $session->closed_at,
            ];

            /**
             * ===============================
             * SYSTEM LOG (D.3)
             * ===============================
             */
            SystemLog::create([
                'event'       => 'chat_close',
                'entity_type' => 'chat_session',
                'entity_id'   => $session->id,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()->role,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => $request->ip(),
                'meta'        => json_encode([
                    'action' => 'agent_close_chat',
                ]),
            ]);
        });

        return response()->json([
            'status'  => 'ok',
            'message' => 'Chat closed successfully',
        ]);
    }
}
