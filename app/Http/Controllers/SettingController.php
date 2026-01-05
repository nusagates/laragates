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

    /* ------------------- Unified Save -------------------- */
    public function save(Request $request)
    {
        $setting = AppSetting::first();
        
        if (!$setting) {
            $setting = AppSetting::create([
                'company_name' => 'WABA',
                'timezone' => 'Asia/Jakarta',
            ]);
        }
        
        // Save general settings if provided
        if ($request->has('general')) {
            $general = $request->validate([
                'general.company_name' => 'required|string',
                'general.timezone' => 'required|string',
            ]);
            $setting->update($general['general']);
        }
        
        // Save WhatsApp settings if provided
        if ($request->has('whatsapp')) {
            $whatsapp = $request->validate([
                'whatsapp.provider' => 'nullable|string',
                'whatsapp.api_key' => 'nullable|string',
                'whatsapp.webhook_url' => 'nullable|string',
            ]);
            
            // Map frontend fields to database fields
            $setting->update([
                'wa_api_key' => $whatsapp['whatsapp']['api_key'] ?? null,
                'wa_webhook' => $whatsapp['whatsapp']['webhook_url'] ?? null,
            ]);
        }
        
        // Save preferences if provided
        if ($request->has('preferences')) {
            $preferences = $request->validate([
                'preferences.auto_assign' => 'boolean',
                'preferences.notify_sound' => 'boolean',
            ]);
            
            // Map frontend fields to database fields
            $setting->update([
                'auto_assign_ticket' => $preferences['preferences']['auto_assign'] ?? false,
                'notif_sound' => $preferences['preferences']['notify_sound'] ?? false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully'
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
