<?php

namespace App\Http\Controllers;

use App\Models\WaMenu;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        return Inertia::render('Menu/Create');
    }

    public function edit(WaMenu $menu)
    {
        return Inertia::render('Menu/Edit', [
            'menu' => $menu
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'         => 'required',
            'title'       => 'required',
            'action_type' => 'required',
            'reply_text'  => 'nullable',
            'order'       => 'nullable|integer',
        ]);

        WaMenu::create($data);

        return redirect()->route('menu.index');
    }

    public function update(Request $request, WaMenu $menu)
    {
        $data = $request->validate([
            'key'         => 'required',
            'title'       => 'required',
            'action_type' => 'required',
            'reply_text'  => 'nullable',
            'order'       => 'nullable|integer',
        ]);

        $menu->update($data);

        return redirect()->route('menu.index');
    }

    public function destroy(WaMenu $menu)
    {
        $menu->delete();
        return redirect()->route('menu.index');
    }
}
