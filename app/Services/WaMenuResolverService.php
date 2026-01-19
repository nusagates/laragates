<?php

namespace App\Services;

use App\Models\WaMenu;
use App\Models\ChatMessage;
use App\Services\SystemLogService;

class WaMenuResolverService
{
    public static function handle($session, $incomingMessage): void
    {
        /**
         * ===============================
         * 1. GUARD CLAUSE
         * ===============================
         */
        if (!$incomingMessage) {
            return;
        }

        if (!$incomingMessage->message) {
            return;
        }

        // Jangan proses pesan bot
        if ($incomingMessage->is_bot) {
            return;
        }

        /**
         * ===============================
         * 2. NORMALISASI INPUT
         * ===============================
         */
        $key = trim($incomingMessage->message);

        /**
         * ===============================
         * 3. CARI MENU BERDASARKAN KEY
         * ===============================
         */
        $menu = WaMenu::where('key', $key)
            ->where('is_active', true)
            ->first();

        if (!$menu) {
            return;
        }

        /**
         * ===============================
         * 4. HANDLE ACTION TYPE
         * ===============================
         */
        switch ($menu->action_type) {

            /**
             * ---------- ASK INPUT ----------
             */
            case 'ask_input':

                ChatMessage::create([
                    'chat_session_id' => $session->id,
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
                        'key'    => $menu->key,
                        'title'  => $menu->title,
                        'action' => $menu->action_type,
                    ],
                    meta: [
                        'source' => 'menu',
                    ]
                );

                break;

            /**
             * ---------- HANDOVER ----------
             */
            case 'handover':

                // tandai session masuk mode handover
                $session->update([
                    'is_handover' => true,
                ]);

                SystemLogService::record(
                    event: 'wa_menu_handover',
                    entityType: 'wa_menu',
                    entityId: $menu->id,
                    newValues: [
                        'key'    => $menu->key,
                        'title'  => $menu->title,
                        'action' => $menu->action_type,
                    ],
                    meta: [
                        'source' => 'menu',
                    ]
                );

                break;

            /**
             * ---------- DEFAULT ----------
             */
            default:
                return;
        }
    }
}
