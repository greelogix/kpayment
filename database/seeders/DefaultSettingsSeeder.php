<?php

namespace Greelogix\KPayment\Database\Seeders;

use Illuminate\Database\Seeder;
use Greelogix\KPayment\Models\SiteSetting;

/**
 * Default settings seeder for KNET Payment package
 */
class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // KNET Configuration
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
                'description' => 'Default Currency Code (414 = KWD, 840 = USD, 682 = SAR)',
            ],
            [
                'key' => 'kpayment_language',
                'value' => config('kpayment.language', 'EN'),
                'type' => 'select',
                'group' => 'payment',
                'description' => 'Default Language (EN or AR)',
            ],
            [
                'key' => 'kpayment_action',
                'value' => config('kpayment.action', '1'),
                'type' => 'select',
                'group' => 'payment',
                'description' => 'Default Action (1 = Purchase, 2 = Refund)',
            ],
            [
                'key' => 'kpayment_kfast_enabled',
                'value' => config('kpayment.kfast_enabled', false) ? '1' : '0',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable KFAST (KNET Fast Payment)',
            ],
            [
                'key' => 'kpayment_apple_pay_enabled',
                'value' => config('kpayment.apple_pay_enabled', false) ? '1' : '0',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable Apple Pay support',
            ],
            [
                'key' => 'kpayment_apple_pay_certificate',
                'value' => config('kpayment.apple_pay_certificate', ''),
                'type' => 'textarea',
                'group' => 'features',
                'description' => 'Apple Pay Payment Processing Certificate (PEM format)',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Default KNET settings seeded successfully.');
    }
}

