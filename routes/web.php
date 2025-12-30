<?php

use Illuminate\Support\Facades\Route;
use Greelogix\KPay\Http\Controllers\ResponseController;
use Greelogix\KPay\Http\Controllers\RedirectController;

// Payment routes
Route::middleware('web')->group(function () {
    // Payment response routes (CSRF exempt)
    Route::post('kpay/response', [ResponseController::class, 'handle'])
        ->name('kpay.response')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    Route::get('kpay/response', [ResponseController::class, 'handle'])
        ->name('kpay.response.get')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    // Payment redirect route - auto-submits form to KNET
    // Note: This route is accessible without authentication for payment processing
    Route::get('kpay/redirect/{paymentId}', [RedirectController::class, 'redirect'])
        ->name('kpay.redirect');

    // Payment success and error pages
    Route::get('payment/success', function () {
        return view('kpay::payment.success');
    })->name('kpay.success');

    Route::get('payment/error', function () {
        return view('kpay::payment.error');
    })->name('kpay.error');
});

