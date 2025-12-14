<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * list system settings.
     * Allowed for both Admin and Customer.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update a system setting.
     * Allowed for Admin only.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        $setting = Setting::where('key', $key)->firstOrFail();

        $value = $request->value;

        // If 'value' is a string but is valid JSON, decode it to ensure it's stored as array/object
        // This supports the 'value' => 'array' cast on the model.
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        $setting->update(['value' => $value]);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'data' => $setting,
        ]);
    }
}
