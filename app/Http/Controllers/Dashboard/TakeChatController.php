<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\SystemLog;
use App\Services\Security\ActionGuardService;
use App\Services\Security\RateMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TakeChatController extends Controller
{
    public function take(Request $request, ChatSession $session)
    {
        // 🔒 D5.3 — ENFORCEMENT
        ActionGuardService::check('chat_take', 5);

        // 📊 D5.1 + D5.2 — MONITOR
        RateMonitorService::check('chat_take', 5);

        DB::transaction(function () use ($session, $request) {

            // lock row
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->firstOrFail();

            // denied: already taken
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

            $oldValues = [
                'assigned_to' => $session->assigned_to,
                'status'      => $session->status,
            ];

            $session->update([
                'assigned_to'        => Auth::id(),
                'last_agent_read_at' => now(),
            ]);

            $newValues = [
                'assigned_to' => $session->assigned_to,
                'status'      => $session->status,
            ];

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
