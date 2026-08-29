<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = SystemSetting::pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required','string','max:150'],
            'recruitment_email' => ['required','email','max:150'],
            'contact_number' => ['nullable','string','max:50'],
            'office_address' => ['nullable','string','max:500'],
            'default_application_status' => ['required','string','max:80'],
        ]);
        foreach ($data as $key => $value) SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        return response()->json(['message' => 'System settings updated successfully.']);
    }
}
