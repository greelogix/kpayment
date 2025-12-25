<?php

namespace Greelogix\KPayment\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Greelogix\KPayment\Models\SiteSetting;

class SiteSettingController extends Controller
{
    /**
     * List of critical settings that cannot be deleted
     */
    protected array $protectedSettings = [
        'kpayment_tranportal_id',
        'kpayment_tranportal_password',
        'kpayment_resource_key',
        'kpayment_base_url',
        'kpayment_test_mode',
        'kpayment_response_url',
        'kpayment_error_url',
        'kpayment_currency',
        'kpayment_language',
        'kpayment_action',
        'kpayment_kfast_enabled',
        'kpayment_apple_pay_enabled',
    ];

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
     * All values are seeded from database only, no config/env dependencies
     */
    protected function ensureDefaultSettings(): void
    {
        $defaultSettings = [
            [
                'key' => 'kpayment_tranportal_id',
                'value' => '',
                'type' => 'text',
                'group' => 'api',
                'description' => 'KNET Tranportal ID (Not required for testing. Required for production - provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_tranportal_password',
                'value' => '',
                'type' => 'password',
                'group' => 'api',
                'description' => 'KNET Tranportal Password (Not required for testing. Required for production - provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_resource_key',
                'value' => '',
                'type' => 'password',
                'group' => 'api',
                'description' => 'KNET Resource Key (Not required for testing. Required for production - provided by your acquiring bank)',
            ],
            [
                'key' => 'kpayment_base_url',
                'value' => 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm',
                'type' => 'text',
                'group' => 'api',
                'description' => 'KNET Base URL (Test: https://kpaytest.com.kw/kpg/PaymentHTTP.htm, Production: https://www.kpay.com.kw/kpg/PaymentHTTP.htm)',
            ],
            [
                'key' => 'kpayment_test_mode',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'api',
                'description' => 'Test Mode (Yes = Testing - no credentials needed, No = Production - credentials required. When disabling, ensure Base URL is set to production URL)',
            ],
            [
                'key' => 'kpayment_response_url',
                'value' => '',
                'type' => 'url',
                'group' => 'api',
                'description' => 'Response URL (Where KNET redirects after payment processing)',
            ],
            [
                'key' => 'kpayment_error_url',
                'value' => '',
                'type' => 'url',
                'group' => 'api',
                'description' => 'Error URL (Where KNET redirects on payment errors)',
            ],
            [
                'key' => 'kpayment_currency',
                'value' => '414',
                'type' => 'text',
                'group' => 'payment',
                'description' => 'Default Currency Code (414 = KWD, 840 = USD, 682 = SAR)',
            ],
            [
                'key' => 'kpayment_language',
                'value' => 'EN',
                'type' => 'select',
                'group' => 'payment',
                'description' => 'Default Language (EN or AR)',
            ],
            [
                'key' => 'kpayment_action',
                'value' => '1',
                'type' => 'select',
                'group' => 'payment',
                'description' => 'Default Action (1 = Purchase, 2 = Refund)',
            ],
            [
                'key' => 'kpayment_kfast_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable KFAST (KNET Fast Payment)',
            ],
            [
                'key' => 'kpayment_apple_pay_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable Apple Pay support',
            ],
            [
                'key' => 'kpayment_apple_pay_certificate',
                'value' => '',
                'type' => 'textarea',
                'group' => 'features',
                'description' => 'Apple Pay Payment Processing Certificate (PEM format)',
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
        $validated = $request->validate([
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string|max:65535',
            'settings.*.type' => ['required', 'string', Rule::in(['text', 'password', 'url', 'textarea', 'boolean', 'select'])],
            'settings.*.group' => 'required|string|max:255',
            'settings.*.description' => 'nullable|string|max:1000',
        ]);

        // Additional validation for specific setting types
        $errors = [];
        $testModeValue = null;
        $baseUrlValue = null;
        
        foreach ($validated['settings'] as $index => $setting) {
            $key = $setting['key'];
            $value = $setting['value'] ?? '';
            $type = $setting['type'];

            // Store test mode and base URL for cross-validation
            if ($key === 'kpayment_test_mode') {
                $testModeValue = $value;
            }
            if ($key === 'kpayment_base_url') {
                $baseUrlValue = $value;
            }

            // Validate URLs
            if ($type === 'url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors["settings.{$index}.value"] = "The {$key} must be a valid URL.";
            }

            // Validate language
            if ($key === 'kpayment_language' && !empty($value) && !in_array(strtoupper($value), ['EN', 'AR'])) {
                $errors["settings.{$index}.value"] = "The language must be either EN or AR.";
            }

            // Validate currency code
            if ($key === 'kpayment_currency' && !empty($value) && !preg_match('/^\d{3}$/', $value)) {
                $errors["settings.{$index}.value"] = "The currency must be a 3-digit ISO code.";
            }

            // Validate boolean values
            if ($type === 'boolean' && !empty($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
                $errors["settings.{$index}.value"] = "The {$key} must be a valid boolean value.";
            }
        }

        // Production mode validation: Ensure production URL when test mode is disabled
        if ($testModeValue === '0' && !empty($baseUrlValue)) {
            $testUrl = 'https://kpaytest.com.kw';
            if (strpos($baseUrlValue, $testUrl) !== false) {
                $testModeIndex = array_search('kpayment_test_mode', array_column($validated['settings'], 'key'));
                $baseUrlIndex = array_search('kpayment_base_url', array_column($validated['settings'], 'key'));
                if ($testModeIndex !== false) {
                    $errors["settings.{$testModeIndex}.value"] = "Test mode is disabled but test URL is being used. Please use production URL: https://www.kpay.com.kw/kpg/PaymentHTTP.htm";
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->withErrors($errors)
                ->withInput();
        }

        // Use transaction for data integrity
        DB::transaction(function () use ($validated) {
            foreach ($validated['settings'] as $setting) {
                $sanitizedValue = $this->sanitizeValue($setting['value'] ?? '', $setting['type'], $setting['key']);
                
                SiteSetting::updateOrCreate(
                    ['key' => $setting['key']],
                    [
                        'value' => $sanitizedValue,
                        'type' => $setting['type'],
                        'group' => $setting['group'],
                        'description' => $this->sanitizeString($setting['description'] ?? ''),
                    ]
                );
            }
        });

        return redirect()->route('kpayment.admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Update a single setting
     */
    public function update(Request $request, string $key)
    {
        // Validate key format
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->with('error', 'Invalid setting key format.');
        }

        $validated = $request->validate([
            'value' => 'nullable|string|max:65535',
            'type' => ['required', 'string', Rule::in(['text', 'password', 'url', 'textarea', 'boolean', 'select'])],
            'group' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Additional validation based on key and type
        $value = $validated['value'] ?? '';
        $type = $validated['type'];

        // Validate URLs
        if ($type === 'url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->withErrors(['value' => 'The value must be a valid URL.'])
                ->withInput();
        }

        // Validate language
        if ($key === 'kpayment_language' && !empty($value) && !in_array(strtoupper($value), ['EN', 'AR'])) {
            return redirect()->route('kpayment.admin.settings.index')
                ->withErrors(['value' => 'The language must be either EN or AR.'])
                ->withInput();
        }

        // Validate currency code
        if ($key === 'kpayment_currency' && !empty($value) && !preg_match('/^\d{3}$/', $value)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->withErrors(['value' => 'The currency must be a 3-digit ISO code.'])
                ->withInput();
        }

        // Validate boolean values
        if ($type === 'boolean' && !empty($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
            return redirect()->route('kpayment.admin.settings.index')
                ->withErrors(['value' => 'The value must be a valid boolean value.'])
                ->withInput();
        }

        // Production mode validation: When disabling test mode, ensure production URL
        if ($key === 'kpayment_test_mode' && $value === '0') {
            $baseUrl = SiteSetting::getValue('kpayment_base_url', '');
            $testUrl = 'https://kpaytest.com.kw';
            if (!empty($baseUrl) && strpos($baseUrl, $testUrl) !== false) {
                return redirect()->route('kpayment.admin.settings.index')
                    ->with('error', 'Cannot disable test mode while using test URL. Please update Base URL to production URL first: https://www.kpay.com.kw/kpg/PaymentHTTP.htm')
                    ->withInput();
            }
        }

        // Production mode validation: When setting base URL, check test mode
        if ($key === 'kpayment_base_url' && !empty($value)) {
            $testMode = SiteSetting::getValue('kpayment_test_mode', '1');
            $testUrl = 'https://kpaytest.com.kw';
            $productionUrl = 'https://www.kpay.com.kw';
            
            if ($testMode === '0' && strpos($value, $testUrl) !== false) {
                return redirect()->route('kpayment.admin.settings.index')
                    ->with('error', 'Cannot use test URL when test mode is disabled. Please use production URL: https://www.kpay.com.kw/kpg/PaymentHTTP.htm')
                    ->withInput();
            }
            
            if ($testMode === '1' && strpos($value, $productionUrl) !== false) {
                return redirect()->route('kpayment.admin.settings.index')
                    ->with('warning', 'You are using production URL while test mode is enabled. For production, disable test mode first.')
                    ->withInput();
            }
        }

        $sanitizedValue = $this->sanitizeValue($value, $type, $key);

        SiteSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $sanitizedValue,
                'type' => $validated['type'],
                'group' => $validated['group'],
                'description' => $this->sanitizeString($validated['description'] ?? ''),
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
        // Validate key format
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->with('error', 'Invalid setting key format.');
        }

        // Prevent deletion of critical settings
        if (in_array($key, $this->protectedSettings)) {
            return redirect()->route('kpayment.admin.settings.index')
                ->with('error', 'This setting cannot be deleted as it is critical for the package functionality.');
        }

        $setting = SiteSetting::where('key', $key)->first();

        if (!$setting) {
            return redirect()->route('kpayment.admin.settings.index')
                ->with('error', 'Setting not found.');
        }

        $setting->delete();

        return redirect()->route('kpayment.admin.settings.index')
            ->with('success', 'Setting deleted successfully.');
    }

    /**
     * Sanitize value based on type
     */
    protected function sanitizeValue(string $value, string $type, string $key): string
    {
        if (empty($value)) {
            return '';
        }

        switch ($type) {
            case 'url':
                // Validate and sanitize URL
                $value = filter_var(trim($value), FILTER_SANITIZE_URL);
                break;
            case 'password':
                // Don't sanitize passwords, just trim
                $value = trim($value);
                break;
            case 'boolean':
                // Normalize boolean values
                $value = in_array(strtolower($value), ['1', 'true', 'yes', 'on']) ? '1' : '0';
                break;
            case 'textarea':
                // Sanitize textarea (allow more characters)
                $value = strip_tags(trim($value));
                break;
            case 'text':
            case 'select':
            default:
                // Sanitize text input
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                break;
        }

        // Special handling for specific keys
        if ($key === 'kpayment_language') {
            $value = strtoupper($value);
        }

        if ($key === 'kpayment_currency') {
            $value = preg_replace('/[^0-9]/', '', $value);
        }

        return $value;
    }

    /**
     * Sanitize string input
     */
    protected function sanitizeString(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}


