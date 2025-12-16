<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaMenu;
use App\Services\MenuEngine;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class WaMenuController extends Controller
{
    public function index()
    {
        return Inertia::render('Menu/Index', [
            'menus' => WaMenu::orderBy('order')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Menu/Form', [
            'menu' => null
        ]);
    }

    public function edit(WaMenu $menu)
    {
        return Inertia::render('Menu/Form', [
            'menu' => $menu
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMenu($request);

        WaMenu::create($data);

        return redirect()->route('menu.index')
            ->with('success', 'Menu berhasil ditambahkan');
    }

    public function update(Request $request, WaMenu $menu)
    {
        $data = $this->validateMenu($request, $menu->id);

        $menu->update($data);

        return redirect()->route('menu.index')
            ->with('success', 'Menu berhasil diperbarui');
    }

    public function destroy(WaMenu $menu)
    {
        $menu->delete();

        return redirect()->route('menu.index')
            ->with('success', 'Menu berhasil dihapus');
    }

    /**
     * ===============================
     * VALIDASI & NORMALISASI MENU
     * ===============================
     */
    protected function validateMenu(Request $request, $ignoreId = null): array
    {
        $request->validate([
            'key'         => 'required',
            'title'       => 'required|string|max:255',
            'action_type' => 'required|in:auto_reply,ask_input,handover',
            'reply_text'  => 'nullable|string',
            'order'       => 'nullable|integer',
        ]);

        // 🔒 NORMALISASI KEY (PAKAI ENGINE)
        $normalizedKey = MenuEngine::normalizeKey($request->key);

        if (! $normalizedKey) {
            throw ValidationException::withMessages([
                'key' => 'Key menu harus berupa angka (contoh: 1, 2, 3)',
            ]);
        }

        // 🔒 CEK DUPLIKASI KEY
        $exists = WaMenu::where('key', $normalizedKey)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'key' => "Key menu '{$normalizedKey}' sudah digunakan",
            ]);
        }

        return [
            'key'         => $normalizedKey,
            'title'       => $request->title,
            'action_type' => $request->action_type,
            'reply_text'  => $request->reply_text,
            'order'       => $request->order ?? 0,
            'is_active'   => true,
        ];
    }
}
