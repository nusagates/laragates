<?php

namespace App\Support;

use App\Models\Broadcast;

class BroadcastLog
{
    public static function meta(
        Broadcast $broadcast,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return array_merge([
            'action' => $action,
            'object' => 'broadcast',

            'broadcast' => [
                'id'        => $broadcast->id,
                'name'      => $broadcast->name ?? null,
                'status'    => $broadcast->status ?? null,
                'channel'   => $broadcast->channel ?? 'whatsapp',
                'total'     => $broadcast->total_recipients ?? null,
                'scheduled' => $broadcast->scheduled_at ?? null,
            ],

            'description' => $description,

            'context' => [
                'source' => 'broadcast_module',
                'actor'  => auth()->user()->role ?? 'system',
            ],
        ], $extra);
    }

    public static function highRisk(
        Broadcast $broadcast,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return self::meta(
            $broadcast,
            $action,
            $description,
            array_merge([
                'risk'  => 'high',
                'alert' => true,
            ], $extra)
        );
    }
}
