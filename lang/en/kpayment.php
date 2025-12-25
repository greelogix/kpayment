<?php

return [
    // Payment Pages
    'payment' => [
        'success' => [
            'title' => 'Payment Success - KNET Payment',
            'heading' => 'Payment Successful!',
            'message' => 'Your payment has been processed successfully.',
            'details' => 'Payment Details',
            'order_id' => 'Order ID',
            'transaction_id' => 'Transaction ID',
            'amount' => 'Amount',
            'reference' => 'Reference',
            'authorization' => 'Authorization',
            'return_home' => 'Return to Home',
        ],
        'error' => [
            'title' => 'Payment Error - KNET Payment',
            'heading' => 'Payment Failed',
            'default_message' => 'Your payment could not be processed. Please try again.',
            'details' => 'Payment Details',
            'order_id' => 'Order ID',
            'transaction_id' => 'Transaction ID',
            'result' => 'Result',
            'amount' => 'Amount',
            'return_home' => 'Return to Home',
            'try_again' => 'Try Again',
        ],
        'form' => [
            'title' => 'Redirecting to KNET Payment Gateway...',
            'redirecting' => 'Redirecting to KNET Payment Gateway...',
            'please_wait' => 'Please wait while we process your payment',
        ],
    ],

    // Response Messages
    'response' => [
        'success' => 'Payment completed successfully',
        'failed' => 'Payment failed',
        'error' => 'An error occurred while processing your payment. Please try again.',
        'invalid_hash' => 'Invalid payment response hash',
        'track_id_not_found' => 'Track ID not found in response',
        'payment_not_found' => 'Payment not found',
        'unexpected_error' => 'An unexpected error occurred while processing payment response.',
    ],

    // Admin Panel
    'admin' => [
        'settings' => [
            'title' => 'KNET Payment Settings',
            'heading' => 'KNET Payment Settings',
            'description' => 'Configure your KNET payment gateway settings',
            'updated_successfully' => 'Settings updated successfully.',
            'setting_updated_successfully' => 'Setting updated successfully.',
            'setting_deleted_successfully' => 'Setting deleted successfully.',
            'invalid_key_format' => 'Invalid setting key format.',
            'setting_not_found' => 'Setting not found.',
            'cannot_delete_protected' => 'This setting cannot be deleted as it is critical for the package functionality.',
            'cannot_disable_test_mode' => 'Cannot disable test mode while using test URL. Please update Base URL to production URL first: https://www.kpay.com.kw/kpg/PaymentHTTP.htm',
            'cannot_use_test_url' => 'Cannot use test URL when test mode is disabled. Please use production URL: https://www.kpay.com.kw/kpg/PaymentHTTP.htm',
            'production_url_warning' => 'You are using production URL while test mode is enabled. For production, disable test mode first.',
        ],
        'payment_methods' => [
            'title' => 'Payment Methods',
            'heading' => 'Payment Methods Management',
            'status_updated_successfully' => 'Status updated successfully.',
        ],
    ],

    // Common
    'common' => [
        'currency' => [
            'kwd' => 'KWD',
        ],
    ],
];

