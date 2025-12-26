<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TakeChatController extends Controller
{
    /**
     * Assign waiting chat ke agent yang login
     */
    public function __invoke(Request $request, ChatSession $chat)
    {
        $user = Auth::user();

        // Optional: pastikan role agent
        if ($user->role !== 'agent') {
            abort(403, 'Only agent can take chat');
        }

        DB::transaction(function () use ($chat, $user) {

            // Lock row untuk cegah double take
            $chat->lockForUpdate();

            // Jika sudah diambil agent lain
            if ($chat->assigned_to !== null) {
                abort(409, 'Chat already assigned');
            }

            // Assign ke agent
            $chat->update([
                'assigned_to' => $user->id,
            ]);
        });

        return back()->with('success', 'Chat berhasil diambil');
    }
}
