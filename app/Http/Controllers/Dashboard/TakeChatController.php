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
        DB::transaction(function () use ($session) {

            // ===============================
            // 🔒 LOCK ROW (ANTI DOUBLE TAKE)
            // ===============================
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($session->assigned_to) {
                abort(409, 'Chat already taken');
            }

            // ===============================
            // ASSIGN CHAT TO AGENT
            // ===============================
            $session->update([
                'assigned_to'        => Auth::id(),
                'last_agent_read_at' => now(),
            ]);

            // ===============================
            // 🧾 SYSTEM LOG (COMPLIANCE)
            // ===============================
            SystemLog::create([
                'event'       => 'chat_take',
                'source'      => 'system',
                'description' => 'Agent mengambil chat session #' . $session->id,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()->role ?? 'agent',
                'ip_address'  => request()->ip(),
                'meta'        => json_encode([
                    'chat_session_id' => $session->id,
                    'agent_id'        => Auth::id(),
                ]),
            ]);
        });

        return redirect()->route('chat');
    }
}
