<?php

use Illuminate\Support\Facades\Route;
use Greelogix\KPayment\Http\Controllers\ResponseController;
use Greelogix\KPayment\Http\Controllers\Admin\SiteSettingController;

// Payment response route (CSRF exempt)
Route::post('kpayment/response', [ResponseController::class, 'handle'])
    ->name('kpayment.response')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::get('kpayment/response', [ResponseController::class, 'handle'])
    ->name('kpayment.response.get')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// Admin routes
Route::prefix('admin/kpayment')->name('kpayment.admin.')->middleware(['web', 'auth'])->group(function () {
    // Settings routes
    Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SiteSettingController::class, 'store'])->name('settings.store');
    Route::put('settings/{key}', [SiteSettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/{key}', [SiteSettingController::class, 'destroy'])->name('settings.destroy');

    // Payment methods routes
    Route::get('payment-methods', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('payment-methods', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::post('payment-methods/seed', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'seed'])->name('payment-methods.seed');
    Route::put('payment-methods/{paymentMethod}', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('payment-methods/{paymentMethod}', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::post('payment-methods/{paymentMethod}/toggle-status', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggle-status');
});

