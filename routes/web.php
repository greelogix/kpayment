<?php

use Illuminate\Support\Facades\Route;
use Greelogix\KPay\Http\Controllers\ResponseController;
use Greelogix\KPay\Facades\KPay;
use Greelogix\KPay\Models\KPayPayment;

// Ensure routes are loaded within web middleware group
Route::middleware('web')->group(function () {
    // Payment response route (CSRF exempt)
    Route::post('kpay/response', [ResponseController::class, 'handle'])
        ->name('kpay.response')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    Route::get('kpay/response', [ResponseController::class, 'handle'])
        ->name('kpay.response.get')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    // Payment redirect route - auto-submits form to KNET
    Route::get('kpay/redirect/{paymentId}', function ($paymentId) {
        $payment = KPayPayment::findOrFail($paymentId);
        $formData = $payment->request_data ?? [];
        
        // Remove payment_id from form data
        unset($formData['payment_id']);
        
        // Get base URL from config
        $baseUrl = config('kpay.base_url');
        if (empty($baseUrl)) {
            $testMode = config('kpay.test_mode', true);
            $baseUrl = $testMode 
                ? 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'
                : 'https://www.kpay.com.kw/kpg/PaymentHTTP.htm';
        }
        
        return view('kpay::payment.form', [
            'formUrl' => $baseUrl,
            'formData' => $formData,
        ]);
    })->name('kpay.redirect');

    // Payment success and error pages
    Route::get('payment/success', function () {
        return view('kpay::payment.success');
    })->name('kpay.success');

    Route::get('payment/error', function () {
        return view('kpay::payment.error');
    })->name('kpay.error');
});

