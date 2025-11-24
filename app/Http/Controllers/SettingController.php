<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        
        return Inertia::render('Settings/Index', [
            'settings' => $setting
        ]);
    }

    public function saveGeneral(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'timezone' => 'required'
        ]);

        $setting = Setting::first();
        $setting->update([
            'company_name' => $request->company_name,
            'timezone' => $request->timezone
        ]);

        return back()->with('success', 'General settings updated');
    }

    public function saveWhatsApp(Request $request)
    {
        $request->validate([
            'wa_phone_number' => 'required',
            'wa_webhook_url' => 'required',
            'wa_api_key' => 'required',
        ]);

        $setting = Setting::first();
        $setting->update([
            'wa_phone_number' => $request->wa_phone_number,
            'wa_webhook_url' => $request->wa_webhook_url,
            'wa_api_key' => $request->wa_api_key
        ]);

        return back()->with('success', 'WhatsApp API updated');
    }

    public function savePreferences(Request $request)
    {
        $setting = Setting::first();
        $setting->update([
            'notif_sound' => $request->notif_sound ?? false,
            'notif_desktop' => $request->notif_desktop ?? false,
            'auto_assign' => $request->auto_assign ?? false,
        ]);

        return back()->with('success', 'Preferences updated');
    }
}
