<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use Illuminate\Http\Request;

class AiSettingController extends Controller
{
    public function show()
    {
        return response()->json(
            AiSetting::first()
        );
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'     => 'required|boolean',
            'daily_quota' => 'required|integer|min:1',
        ]);

        $setting = AiSetting::first();
        $setting->update($data);

        return response()->json([
            'status' => 'ok',
            'setting' => $setting,
        ]);
    }
}
