<?php

namespace App\Services;

use App\Models\WaMenu;

class MenuEngine
{
    /**
     * =====================================================
     * NORMALISASI INPUT KEY DARI USER
     * =====================================================
     * Contoh:
     *  "01"        → "1"
     *  "1️⃣"       → "1"
     *  "menu 1"    → "1"
     *  "menu satu" → null
     */
    public static function normalizeKey(string $input): ?string
    {
        preg_match('/\d+/', $input, $matches);

        if (empty($matches)) {
            return null;
        }

        // Hilangkan leading zero (01 -> 1)
        return ltrim($matches[0], '0') ?: '0';
    }

    /**
     * =====================================================
     * MENU UTAMA (PARENT = NULL)
     * =====================================================
     */
    public static function mainMenu(): string
    {
        return self::buildMenu(
            null,
            "📌 *MENU UTAMA*"
        );
    }

    /**
     * =====================================================
     * SUB MENU BERDASARKAN PARENT ID
     * =====================================================
     */
    public static function subMenu(int $parentId, string $title): string
    {
        return self::buildMenu(
            $parentId,
            "📂 *{$title}*"
        );
    }

    /**
     * =====================================================
     * CORE MENU BUILDER (PRIVATE)
     * =====================================================
     * Dipakai oleh:
     * - mainMenu()
     * - subMenu()
     */
    protected static function buildMenu(?int $parentId, string $header): string
    {
        $menus = WaMenu::where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        if ($menus->isEmpty()) {
            return
                "🙏 *Menu belum tersedia*\n\n" .
                "Ketik *0* untuk kembali ke Menu Utama.";
        }

        $text = "{$header}\n\n";

        foreach ($menus as $menu) {
            $text .= "{$menu->key}️⃣ {$menu->title}\n";
        }

        $text .= "\nKetik angka menu";
        $text .= "\nKetik *0* untuk kembali";

        return $text;
    }

    /**
     * =====================================================
     * FIND MENU BY KEY (TREE-AWARE & AMAN)
     * =====================================================
     * - Support menu bertingkat
     * - Tidak mengganggu logic lama
     */
    public static function findByKey(string $input, ?int $parentId = null): ?array
    {
        $key = self::normalizeKey($input);

        if (! $key) {
            return null;
        }

        $menu = WaMenu::where('key', $key)
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->first();

        if (! $menu) {
            return null;
        }

        return [
            'id'          => $menu->id,
            'key'         => $menu->key,
            'title'       => $menu->title,
            'reply_text'  => $menu->reply_text,
            'action_type' => $menu->action_type,
            'parent_id'   => $menu->parent_id,
        ];
    }

    /**
     * =====================================================
     * CEK APAKAH MENU PUNYA ANAK (SUBMENU)
     * =====================================================
     */
    public static function hasChildren(int $menuId): bool
    {
        return WaMenu::where('parent_id', $menuId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * =====================================================
     * LIST SEMUA MENU AKTIF (OPSIONAL)
     * =====================================================
     * Biasanya dipakai untuk:
     * - debug
     * - preview menu
     * - admin dashboard
     */
    public static function all()
    {
        return WaMenu::where('is_active', true)
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get();
    }
}
