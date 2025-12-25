<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tranportal ID
    |--------------------------------------------------------------------------
    |
    | Your KNET tranportal ID provided by your acquiring bank.
    |
    | IMPORTANT: For TEST MODE, this can be left empty. KNET test environment
    | does not require credentials for testing.
    |
    | For PRODUCTION, this is REQUIRED and must be provided by your acquiring bank.
    |
    | NOTE: This is a fallback value. The actual value should be configured
    | in the admin panel at /admin/kpayment/settings
    | Settings in admin panel take priority over this value.
    |
    */
    'tranportal_id' => env('KPAYMENT_TRANPORTAL_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Tranportal Password
    |--------------------------------------------------------------------------
    |
    | Your KNET tranportal password provided by your acquiring bank.
    |
    | IMPORTANT: For TEST MODE, this can be left empty. KNET test environment
    | does not require credentials for testing.
    |
    | For PRODUCTION, this is REQUIRED and must be provided by your acquiring bank.
    |
    | NOTE: This is a fallback value. Configure in admin panel for production use.
    |
    */
    'tranportal_password' => env('KPAYMENT_TRANPORTAL_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Resource Key
    |--------------------------------------------------------------------------
    |
    | Your KNET resource key for payment processing.
    |
    | IMPORTANT: For TEST MODE, this can be left empty. KNET test environment
    | does not require credentials for testing.
    |
    | For PRODUCTION, this is REQUIRED and must be provided by your acquiring bank.
    |
    | NOTE: This is a fallback value. Configure in admin panel for production use.
    |
    */
    'resource_key' => env('KPAYMENT_RESOURCE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | KNET Payment Gateway base URL.
    | Test: https://kpaytest.com.kw/kpg/PaymentHTTP.htm
    | Production: https://www.kpay.com.kw/kpg/PaymentHTTP.htm
    |
    | NOTE: This is a fallback value. Configure in admin panel for production use.
    |
    */
    'base_url' => env('KPAYMENT_BASE_URL', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Set to true for test mode, false for production.
    |
    | IMPORTANT: In test mode, KNET does NOT require any credentials
    | (Tranportal ID, Password, or Resource Key). You can test payments
    | without configuring these fields.
    |
    | NOTE: This is a fallback value. Configure in admin panel for production use.
    |
    */
    'test_mode' => env('KPAYMENT_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Response URL
    |--------------------------------------------------------------------------
    |
    | URL where KNET will redirect after payment processing.
    |
    */
    'response_url' => env('KPAYMENT_RESPONSE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Error URL
    |--------------------------------------------------------------------------
    |
    | URL where KNET will redirect on payment errors.
    |
    */
    'error_url' => env('KPAYMENT_ERROR_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency code (ISO 4217).
    |
    */
    'currency' => env('KPAYMENT_CURRENCY', '414'), // 414 = KWD

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | Default language code (AR or EN).
    |
    */
    'language' => env('KPAYMENT_LANGUAGE', 'EN'),

    /*
    |--------------------------------------------------------------------------
    | Action
    |--------------------------------------------------------------------------
    |
    | Transaction action code.
    | 1 = Purchase
    | 2 = Refund
    |
    */
    'action' => env('KPAYMENT_ACTION', '1'),

    /*
    |--------------------------------------------------------------------------
    | KFAST Enabled
    |--------------------------------------------------------------------------
    |
    | Enable KFAST (KNET Fast Payment) support.
    |
    */
    'kfast_enabled' => env('KPAYMENT_KFAST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Apple Pay Enabled
    |--------------------------------------------------------------------------
    |
    | Enable Apple Pay support.
    |
    */
    'apple_pay_enabled' => env('KPAYMENT_APPLE_PAY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Apple Pay Certificate
    |--------------------------------------------------------------------------
    |
    | Apple Pay payment processing certificate path or content.
    |
    */
    'apple_pay_certificate' => env('KPAYMENT_APPLE_PAY_CERTIFICATE', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Model
    |--------------------------------------------------------------------------
    |
    | The model class that will be used to store payment records.
    |
    */
    'payment_model' => \Greelogix\KPayment\Models\KnetPayment::class,

    /*
    |--------------------------------------------------------------------------
    | Payment Method Model
    |--------------------------------------------------------------------------
    |
    | The model class that will be used to store payment methods.
    |
    */
    'payment_method_model' => \Greelogix\KPayment\Models\PaymentMethod::class,
];

