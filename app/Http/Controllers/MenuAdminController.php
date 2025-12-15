<?php

namespace App\Http\Controllers;

use App\Models\WaMenu;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuAdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Menu/Index', [
            'menus' => WaMenu::orderBy('order')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Menu/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'         => 'required|string|max:10',
            'title'       => 'required|string|max:255',
            'action_type' => 'required|in:auto_reply,ask_input,handover',
            'reply_text'  => 'nullable|string',
        ]);

        WaMenu::create([
            'parent_id'   => null,
            'key'         => $data['key'],
            'title'       => $data['title'],
            'action_type' => $data['action_type'],
            'reply_text'  => $data['reply_text'] ?? null,
            'is_active'   => true,
            'order'       => WaMenu::max('order') + 1,
        ]);

        return redirect()->route('menu.index');
    }

    public function edit(WaMenu $menu)
    {
        return Inertia::render('Menu/Create', [
            'menu' => $menu
        ]);
    }

    public function update(Request $request, WaMenu $menu)
    {
        $menu->update(
            $request->only('key', 'title', 'action_type', 'reply_text')
        );

        return redirect()->route('menu.index');
    }

    public function destroy(WaMenu $menu)
    {
        $menu->delete();
        return back();
    }
}
