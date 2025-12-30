<?php

return [
    // Payment Pages
    'payment' => [
        'success' => [
            'title' => 'نجاح الدفع - بوابة الدفع KNET',
            'heading' => 'تم الدفع بنجاح!',
            'message' => 'تم معالجة دفعتك بنجاح.',
            'details' => 'تفاصيل الدفع',
            'order_id' => 'رقم الطلب',
            'transaction_id' => 'رقم المعاملة',
            'amount' => 'المبلغ',
            'reference' => 'المرجع',
            'authorization' => 'التفويض',
            'return_home' => 'العودة إلى الصفحة الرئيسية',
        ],
        'error' => [
            'title' => 'خطأ في الدفع - بوابة الدفع KNET',
            'heading' => 'فشل الدفع',
            'default_message' => 'لم يتم معالجة دفعتك. يرجى المحاولة مرة أخرى.',
            'details' => 'تفاصيل الدفع',
            'order_id' => 'رقم الطلب',
            'transaction_id' => 'رقم المعاملة',
            'result' => 'النتيجة',
            'amount' => 'المبلغ',
            'return_home' => 'العودة إلى الصفحة الرئيسية',
            'try_again' => 'حاول مرة أخرى',
        ],
        'form' => [
            'title' => 'إعادة التوجيه إلى بوابة الدفع KNET...',
            'redirecting' => 'إعادة التوجيه إلى بوابة الدفع KNET...',
            'please_wait' => 'يرجى الانتظار بينما نقوم بمعالجة دفعتك',
        ],
    ],

    // Response Messages
    'response' => [
        'success' => 'تم إتمام الدفع بنجاح',
        'failed' => 'فشل الدفع',
        'error' => 'حدث خطأ أثناء معالجة دفعتك. يرجى المحاولة مرة أخرى.',
        'invalid_hash' => 'رمز التحقق من الاستجابة غير صالح',
        'track_id_not_found' => 'لم يتم العثور على رقم التتبع في الاستجابة',
        'payment_not_found' => 'لم يتم العثور على الدفعة',
        'unexpected_error' => 'حدث خطأ غير متوقع أثناء معالجة استجابة الدفع.',
    ],

    // Redirect/Form Errors
    'redirect' => [
        'payment_not_found' => 'لم يتم العثور على الدفعة (رقم: :id). يرجى بدء الدفع مرة أخرى.',
        'invalid_request_data' => 'بيانات طلب الدفع غير صالحة: :error',
        'gateway_config_error' => 'خطأ في إعدادات بوابة الدفع.',
        'processing_error' => 'حدث خطأ أثناء معالجة طلب الدفع الخاص بك: :error',
    ],

    // Common
    'common' => [
        'currency' => [
            'kwd' => 'د.ك',
        ],
    ],
];

