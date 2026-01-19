<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaMenu;
use App\Services\MenuEngine;
use App\Services\SystemLogService;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class WaMenuController extends Controller
{
    /**
     * ===============================
     * LIST MENU
     * AUDIT: access
     * ===============================
     */
    public function index()
    {
        $menus = WaMenu::orderBy('order')->get();

        SystemLogService::record(
            'menu_view',
            null,
            null,
            null,
            null,
            [
                'description' => 'Opened WhatsApp Menu management page',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'whatsapp_menu',
                    'mode'       => 'manual',
                ],
                'risk' => [
                    'level' => 'low',
                ]
            ]
        );

        return Inertia::render('Menu/Index', [
            'menus' => $menus
        ]);
    }

    /**
     * ===============================
     * CREATE PAGE
     * AUDIT: access
     * ===============================
     */
    public function create()
    {
        SystemLogService::record(
            'menu_create_view',
            null,
            null,
            null,
            null,
            [
                'description' => 'Opened WhatsApp Menu create page',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'whatsapp_menu',
                ],
                'risk' => [
                    'level' => 'low',
                ]
            ]
        );

        return Inertia::render('Menu/Create');
    }

    /**
     * ===============================
     * EDIT PAGE
     * AUDIT: access
     * ===============================
     */
    public function edit(WaMenu $menu)
    {
        SystemLogService::record(
            'menu_edit_view',
            'wa_menu',
            $menu->id,
            null,
            null,
            [
                'description' => 'Opened WhatsApp Menu edit page',
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
                    'level' => 'low',
                ]
            ]
        );

        return Inertia::render('Menu/Edit', [
            'menu' => $menu
        ]);
    }

    /**
     * ===============================
     * STORE MENU
     * AUDIT: data creation
     * ===============================
     */
    public function store(Request $request)
    {
        $data = $this->validateMenu($request);

        $menu = WaMenu::create($data);

        SystemLogService::record(
            'menu_create',
            'wa_menu',
            $menu->id,
            null,
            $menu->toArray(),
            [
                'description' => 'WhatsApp menu "' . $menu->title . '" created',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'whatsapp_menu',
                    'mode'       => 'manual',
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
                        ? 'Menu leads to human handover'
                        : null,
                ]
            ]
        );

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu berhasil ditambahkan');
    }

    /**
     * ===============================
     * UPDATE MENU
     * AUDIT: data modification
     * ===============================
     */
    public function update(Request $request, WaMenu $menu)
    {
        $old = $menu->toArray();

        $data = $this->validateMenu($request, $menu->id);

        $menu->update($data);

        SystemLogService::record(
            'menu_update',
            'wa_menu',
            $menu->id,
            $old,
            $menu->fresh()->toArray(),
            [
                'description' => 'WhatsApp menu "' . $menu->title . '" updated',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'whatsapp_menu',
                    'mode'       => 'manual',
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
                        ? 'Menu leads to human handover'
                        : null,
                ]
            ]
        );

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu berhasil diperbarui');
    }

    /**
     * ===============================
     * DELETE MENU
     * AUDIT: high-risk data removal
     * ===============================
     */
    public function destroy(WaMenu $menu)
    {
        SystemLogService::record(
            'menu_delete',
            'wa_menu',
            $menu->id,
            $menu->toArray(),
            null,
            [
                'description' => 'WhatsApp menu "' . $menu->title . '" deleted',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'whatsapp_menu',
                    'mode'       => 'manual',
                ],
                'menu' => [
                    'id'     => $menu->id,
                    'key'    => $menu->key,
                    'title'  => $menu->title,
                    'action' => $menu->action_type,
                ],
                'risk' => [
                    'level'  => 'high',
                    'reason' => 'Menu deletion affects chatbot flow',
                ]
            ]
        );

        $menu->delete();

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu berhasil dihapus');
    }

    /**
     * ===============================
     * VALIDASI & NORMALISASI MENU
     * (NO BUSINESS CHANGE)
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

        $normalizedKey = MenuEngine::normalizeKey($request->key);

        if (! $normalizedKey) {
            throw ValidationException::withMessages([
                'key' => 'Key menu harus berupa angka (contoh: 1, 2, 3)',
            ]);
        }

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
