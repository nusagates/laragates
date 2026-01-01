<?php

namespace App\Services;

use App\Models\WaMenu;
use App\Models\ChatMessage;
use App\Services\SystemLogService;

class WaMenuResolverService
{
    public static function handle($session, $incomingMessage): void
    {
        if (!$incomingMessage?->message) {
            return;
        }

        $key = trim($incomingMessage->message);

        $menu = WaMenu::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if (!$menu) {
            return;
        }

        if ($menu->action_type !== 'auto_reply') {
            return;
        }

        ChatMessage::create([
    'chat_session_id' => $session->id,
    'sender'          => 'bot', // ✅ FIX
    'message'         => $menu->reply_text,
    'type'            => 'text',
    'is_outgoing'     => true,
    'is_bot'          => true,
]);

        SystemLogService::record(
            event: 'wa_menu_auto_reply',
            entityType: 'wa_menu',
            entityId: $menu->id,
            newValues: [
                'key'   => $menu->key,
                'title' => $menu->title,
            ],
            meta: [
                'source' => 'menu',
            ]
        );
    }
}
