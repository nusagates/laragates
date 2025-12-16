<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    /**
     * ======================================
     * AGENT AMBIL CHAT (LOCK SESSION)
     * ======================================
     */
    public function take(ChatSession $session, Request $request)
    {
        $user = $request->user();

        // ❌ session sudah ditutup
        if ($session->status === 'closed') {
            return response()->json([
                'error' => 'Session sudah ditutup'
            ], 409);
        }

        // ❌ sudah diambil agent lain
        if (
            $session->assigned_to &&
            $session->assigned_to !== $user->id
        ) {
            return response()->json([
                'error' => 'Session sudah diambil agent lain'
            ], 409);
        }

        $session->update([
            'assigned_to' => $user->id,
            'is_handover' => true,
            'bot_state'   => null,
            'bot_context' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat berhasil diambil'
        ]);
    }

    /**
     * ======================================
     * CLOSE CHAT SESSION
     * ======================================
     */
    public function close(ChatSession $session)
    {
        $session->update([
            'status'      => 'closed',
            'is_handover' => false,
            'assigned_to' => null,
            'bot_state'   => null,
            'bot_context' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat ditutup'
        ]);
    }
}
