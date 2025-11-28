<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $setting = AppSetting::first();

        return Inertia::render('Settings/Index', [
            'settings' => $setting
        ]);
    }

    /* ------------------- General Tab -------------------- */
    public function saveGeneral(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string',
            'timezone' => 'required|string',
        ]);

        $setting = AppSetting::first();
        $setting->update($data);

        return back()->with('success', 'General settings updated');
    }

    /* ------------------- WABA API Tab -------------------- */
    public function saveWaba(Request $request)
    {
        $data = $request->validate([
            'wa_phone' => 'nullable|string',
            'wa_webhook' => 'nullable|string',
            'wa_api_key' => 'nullable|string',
        ]);

        $setting = AppSetting::first();
        $setting->update($data);

        return back()->with('success', 'WABA API settings updated');
    }

    public function testWebhook()
    {
        $setting = AppSetting::first();
        $url = $setting->wa_webhook;

        if (!$url) {
            return response()->json(['success' => false, 'message' => 'Webhook URL not set']);
        }

        try {
            $res = @file_get_contents($url);

            return response()->json([
                'success' => true,
                'message' => 'Webhook reachable'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook unreachable: '.$e->getMessage()
            ]);
        }
    }

    /* ------------------- Preferences Tab -------------------- */
    public function savePreferences(Request $request)
    {
        $data = $request->validate([
            'notif_sound' => 'boolean',
            'notif_desktop' => 'boolean',
            'auto_assign_ticket' => 'boolean',
        ]);

        $setting = AppSetting::first();
        $setting->update($data);

        return back()->with('success', 'Preferences updated');
    }
}
