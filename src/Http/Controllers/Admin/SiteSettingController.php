<?php

namespace Greelogix\KPayment\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Greelogix\KPayment\Models\SiteSetting;

class SiteSettingController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        // Ensure default settings exist
        $this->ensureDefaultSettings();
        
        $settings = SiteSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('kpayment::admin.settings.index', compact('settings'));
    }

    /**
     * Ensure default settings exist
     */
    protected function ensureDefaultSettings(): void
    {
        $defaultSettings = [
            [
                'key' => 'kpayment_tranportal_id',
                'value' => config('kpayment.tranportal_id', ''),
                'type' => 'text',
                'group' => 'api',
                'description' => 'KNET Tranportal ID (Provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_tranportal_password',
                'value' => config('kpayment.tranportal_password', ''),
                'type' => 'password',
                'group' => 'api',
                'description' => 'KNET Tranportal Password (Provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_resource_key',
                'value' => config('kpayment.resource_key', ''),
                'type' => 'password',
                'group' => 'api',
                'description' => 'KNET Resource Key (Provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_base_url',
                'value' => config('kpayment.base_url', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'),
                'type' => 'text',
                'group' => 'api',
                'description' => 'KNET Base URL (Test: https://kpaytest.com.kw/kpg/PaymentHTTP.htm, Production: https://www.kpay.com.kw/kpg/PaymentHTTP.htm)',
            ],
            [
                'key' => 'kpayment_test_mode',
                'value' => config('kpayment.test_mode', true) ? '1' : '0',
                'type' => 'boolean',
                'group' => 'api',
                'description' => 'Test Mode (Yes for testing, No for production)',
            ],
            [
                'key' => 'kpayment_response_url',
                'value' => config('kpayment.response_url', ''),
                'type' => 'url',
                'group' => 'api',
                'description' => 'Response URL (Where KNET redirects after payment processing)',
            ],
            [
                'key' => 'kpayment_error_url',
                'value' => config('kpayment.error_url', ''),
                'type' => 'url',
                'group' => 'api',
                'description' => 'Error URL (Where KNET redirects on payment errors)',
            ],
            [
                'key' => 'kpayment_currency',
                'value' => config('kpayment.currency', '414'),
                'type' => 'text',
                'group' => 'payment',
                'description' => 'Default Currency Code (414 = KWD)',
            ],
            [
                'key' => 'kpayment_language',
                'value' => config('kpayment.language', 'EN'),
                'type' => 'text',
                'group' => 'payment',
                'description' => 'Default Language (EN or AR)',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Store or update settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'required|string',
            'settings.*.group' => 'required|string',
            'settings.*.description' => 'nullable|string',
        ]);

        foreach ($request->settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'] ?? '',
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'] ?? '',
                ]
            );
        }

        return redirect()->route('kpayment.admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Update a single setting
     */
    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'nullable',
            'type' => 'required|string',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        SiteSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $request->value ?? '',
                'type' => $request->type,
                'group' => $request->group,
                'description' => $request->description ?? '',
            ]
        );

        return redirect()->route('kpayment.admin.settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    /**
     * Delete a setting
     */
    public function destroy(string $key)
    {
        SiteSetting::where('key', $key)->delete();

        return redirect()->route('kpayment.admin.settings.index')
            ->with('success', 'Setting deleted successfully.');
    }
}


