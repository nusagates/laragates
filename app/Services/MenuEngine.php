<?php

namespace App\Services;

use App\Models\WaMenu;

class MenuEngine
{
    public static function mainMenu(): string
    {
        $menus = WaMenu::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $text = "📌 *MENU UTAMA*\n\n";

        foreach ($menus as $menu) {
            $text .= "{$menu->key}️⃣ {$menu->title}\n";
        }

        $text .= "\nKetik angka menu (1–9)\n";
        $text .= "Ketik *0* untuk kembali ke Menu Utama";

        return $text;
    }

    public static function findByKey(string $key): ?array
{
    $menu = WaMenu::where('key', $key)
        ->where('is_active', true)
        ->first();

    if (!$menu) {
        return null;
    }

    return [
        'id'          => $menu->id,
        'title'       => $menu->title,
        'reply_text'  => $menu->reply_text,
        'action_type' => $menu->action_type,
    ];
}

}
