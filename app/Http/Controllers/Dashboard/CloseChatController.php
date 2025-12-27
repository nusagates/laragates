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
        DB::transaction(function () use ($session) {

            // Lock row untuk hindari double close
            $session = ChatSession::where('id', $session->id)
                ->lockForUpdate()
                ->first();

            // Sudah closed → stop
            if ($session->status === 'closed') {
                abort(409, 'Chat already closed');
            }

            // Security: hanya agent assigned yang boleh close
            if ($session->assigned_to !== Auth::id()) {
                abort(403, 'Unauthorized');
            }

            // Update chat
            $session->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]);

            /**
             * ===============================
             * SYSTEM LOG
             * ===============================
             */
            SystemLog::create([
                'event'       => 'chat_close',
                'entity_type' => 'chat_session',
                'entity_id'   => $session->id,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()->role ?? null,
                'meta'        => json_encode([
                    'assigned_to' => $session->assigned_to,
                    'closed_at'   => now()->toDateTimeString(),
                ]),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        });

        return response()->json([
            'status'  => 'ok',
            'message' => 'Chat closed successfully',
        ]);
    }
}
