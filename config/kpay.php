<?php

// Get resource key - use default test key if in test mode and key is empty
$resourceKey = env('KPAY_RESOURCE_KEY', '');
$testMode = filter_var(env('KPAY_TEST_MODE', true), FILTER_VALIDATE_BOOLEAN);
if ($testMode && empty($resourceKey)) {
    // Default 16-byte test key for encryption (KNET test environment doesn't validate it)
    // Must be exactly 16 bytes for AES-128-CBC
    $resourceKey = 'TEST_KEY_16_BYTE'; // Exactly 16 bytes
}

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
    | Configure via .env or config file.
    |
    */
    'tranportal_id' => env('KPAY_TRANPORTAL_ID', ''),

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
    | Configure via .env or config file.
    |
    */
    'tranportal_password' => env('KPAY_TRANPORTAL_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Resource Key
    |--------------------------------------------------------------------------
    |
    | Your KNET resource key for payment processing.
    |
    | IMPORTANT: For TEST MODE, if left empty, a default test key will be used
    | for encryption (KNET test environment does not validate the key).
    |
    | For PRODUCTION, this is REQUIRED and must be provided by your acquiring bank.
    |
    | Configure via .env or config file.
    |
    */
    'resource_key' => $resourceKey,

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | KNET Payment Gateway base URL.
    | 
    | AUTO-MANAGED: If empty, automatically set based on KPAY_TEST_MODE:
    | - Test: https://kpaytest.com.kw/kpg/PaymentHTTP.htm
    | - Production: https://www.kpay.com.kw/kpg/PaymentHTTP.htm
    |
    | Only set this if you need a custom URL.
    |
    */
    'base_url' => env('KPAY_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Set to true for test mode, false for production.
    |
    | IMPORTANT: This is the PRIMARY setting that controls:
    | - Base URL (auto-set to test or production)
    | - Whether credentials are required
    |
    | In test mode:
    | - Base URL: https://kpaytest.com.kw/kpg/PaymentHTTP.htm
    | - Credentials NOT required
    |
    | In production mode:
    | - Base URL: https://www.kpay.com.kw/kpg/PaymentHTTP.htm
    | - Credentials REQUIRED
    |
    */
    'test_mode' => filter_var(env('KPAY_TEST_MODE', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Response URL
    |--------------------------------------------------------------------------
    |
    | URL where KNET will redirect after payment processing.
    | 
    | AUTO-MANAGED: If empty, automatically generated from APP_URL + /kpay/response
    | 
    | IMPORTANT: Must be a publicly accessible absolute URL.
    | The package provides a default route at /kpay/response.
    |
    | Only set this if you need a custom response URL.
    |
    */
    'response_url' => env('KPAY_RESPONSE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Error URL
    |--------------------------------------------------------------------------
    |
    | URL where KNET will redirect on payment errors.
    | 
    | AUTO-MANAGED: If empty, automatically generated from APP_URL + /kpay/response
    | 
    | IMPORTANT: Must be a publicly accessible absolute URL.
    | The package provides a default route at /kpay/response.
    |
    | Only set this if you need a custom error URL.
    |
    */
    'error_url' => env('KPAY_ERROR_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency code (ISO 4217).
    |
    */
    'currency' => env('KPAY_CURRENCY', '414'), // 414 = KWD

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | Default language code (USA or AR).
    | Note: KPAY requires USA (not EN) for English language.
    |
    */
    'language' => env('KPAY_LANGUAGE', 'USA'),

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
    'action' => env('KPAY_ACTION', '1'),

    /*
    |--------------------------------------------------------------------------
    | KFAST Enabled
    |--------------------------------------------------------------------------
    |
    | Enable KFAST (KNET Fast Payment) support.
    |
    */
    'kfast_enabled' => env('KPAY_KFAST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Apple Pay Enabled
    |--------------------------------------------------------------------------
    |
    | Enable Apple Pay support.
    |
    */
    'apple_pay_enabled' => env('KPAY_APPLE_PAY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Apple Pay Certificate
    |--------------------------------------------------------------------------
    |
    | Apple Pay payment processing certificate path or content.
    |
    */
    'apple_pay_certificate' => env('KPAY_APPLE_PAY_CERTIFICATE', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Table Name
    |--------------------------------------------------------------------------
    |
    | Table name to store payment records.
    | 
    | Options:
    | - 'kpay_payments' (default) - Creates new table
    | - 'payments' - Use existing payments table
    | - 'transactions' - Use existing transactions table
    | - Any custom table name
    |
    | If using existing table, make sure it has the required columns:
    | - id, payment_id, track_id, result, result_code, auth, ref, trans_id,
    |   post_date, udf1-udf5, amount, currency, payment_method, status,
    |   response_data, request_data, created_at, updated_at
    |
    */
    'payment_table' => env('KPAY_PAYMENT_TABLE', 'kpay_payments'),

           /*
           |--------------------------------------------------------------------------
           | Create Payment Table
           |--------------------------------------------------------------------------
           |
           | Set to false if you want to use an existing table and skip migration.
           | Set to true to create the payment table via migration.
           |
           */
           'create_payment_table' => env('KPAY_CREATE_TABLE', true),

           /*
           |--------------------------------------------------------------------------
           | Log Requests
           |--------------------------------------------------------------------------
           |
           | Enable/disable request logging for redirect and response routes.
           | Set to false to disable logging (useful for high-traffic production).
           |
           */
           'log_requests' => env('KPAY_LOG_REQUESTS', true),

       ];

