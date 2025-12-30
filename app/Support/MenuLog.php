<?php

namespace App\Support;

use App\Models\WhatsappMenu;

class MenuLog
{
    public static function meta(
        WhatsappMenu $menu,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return array_merge([
            'description' => $description,
            'audit' => [
                'actor_id'   => auth()->id(),
                'actor_role' => auth()->user()->role ?? null,
                'source'     => 'whatsapp_menu',
            ],
            'menu' => [
                'id'     => $menu->id,
                'key'    => $menu->key,
                'title'  => $menu->title,
                'action' => $menu->action_type,
            ],
            'risk' => [
                'level'  => $menu->action_type === 'handover' ? 'medium' : 'low',
                'reason' => $menu->action_type === 'handover'
                            ? 'Handover to human agent'
                            : null,
            ]
        ], $extra);
    }
}
