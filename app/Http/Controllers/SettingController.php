<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getSettings()
    {
        $setting = Setting::where('key', 'school_settings')->first();
        return response()->json($setting ? $setting->value : new \stdClass());
    }

    public function updateSettings(Request $request)
    {
        $setting = Setting::firstOrNew(['key' => 'school_settings']);
        $setting->value = $request->all();
        $setting->save();

        return response()->json($setting->value);
    }
}
