<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Get all public settings modules.
     *
     * Returns core, contact, ecommerce, and custom_scripts settings in a
     * single keyed object. Sensitive modules (email, SMS, social credentials)
     * are excluded.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Setting::getAllPublic(),
        ]);
    }

    /**
     * Get settings for a specific module.
     *
     * Only publicly whitelisted modules are accessible.
     * Returns 404 for unknown or restricted modules.
     */
    public function show(string $module)
    {
        if (!in_array($module, Setting::PUBLIC_MODULES, true)) {
            return response()->json(['success' => false, 'message' => 'Module not found.'], 404);
        }

        $settings = Setting::getModule($module);

        if ($settings === null) {
            return response()->json(['success' => false, 'message' => 'Module not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['module' => $module, 'settings' => $settings],
        ]);
    }
}
