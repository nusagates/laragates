<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatAgentRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentHeartbeatController extends Controller
{
    public function __construct(
        private readonly ChatAgentRouter $agentRouter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'agent') {
            return response()->json([
                'message' => 'Unauthorized. Only agents can send heartbeat.',
            ], 403);
        }

        $user->update([
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'last_seen' => now(),
        ]);

        if ($user->status !== 'online') {
            $user->update(['status' => 'online']);
        }

        $this->agentRouter->assignPendingTo($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat received',
            'data' => [
                'is_online' => $user->is_online,
                'last_heartbeat_at' => $user->last_heartbeat_at,
            ],
        ]);
    }
}
