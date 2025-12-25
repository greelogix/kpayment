<?php

use Illuminate\Support\Facades\Route;
use Greelogix\KPayment\Http\Controllers\ResponseController;
use Greelogix\KPayment\Http\Controllers\Admin\SiteSettingController;

// Ensure routes are loaded within web middleware group
Route::middleware('web')->group(function () {
    // Payment response route (CSRF exempt)
    Route::post('kpayment/response', [ResponseController::class, 'handle'])
        ->name('kpayment.response')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    Route::get('kpayment/response', [ResponseController::class, 'handle'])
        ->name('kpayment.response.get')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    // Admin routes
    Route::prefix('admin/kpayment')->name('kpayment.admin.')->middleware(['auth'])->group(function () {
        // Settings routes (Full CRUD)
        Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SiteSettingController::class, 'store'])->name('settings.store');
        Route::put('settings/{key}', [SiteSettingController::class, 'update'])
            ->where('key', '[a-z0-9_]+')
            ->name('settings.update');
        Route::delete('settings/{key}', [SiteSettingController::class, 'destroy'])
            ->where('key', '[a-z0-9_]+')
            ->name('settings.destroy');

        // Payment methods routes (view only + toggle status)
        Route::get('payment-methods', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('payment-methods/{paymentMethod}/toggle-status', [\Greelogix\KPayment\Http\Controllers\Admin\PaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggle-status');
    });
});

