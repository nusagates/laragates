<?php

namespace App\Http\Controllers;

use App\Events\TestPushMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestBroadcastController extends Controller
{
    public function index()
    {
        return Inertia::render('TestBroadcast/Index');
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $message = $request->input('message');

        // Broadcast the event
        broadcast(new TestPushMessage($message));

        return response()->json([
            'success' => true,
            'message' => 'Event broadcasted successfully',
            'data' => [
                'message' => $message,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
