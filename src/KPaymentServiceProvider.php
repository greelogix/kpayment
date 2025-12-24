<?php

namespace Greelogix\KPayment;

use Illuminate\Support\ServiceProvider;
use Greelogix\KPayment\Services\KnetService;

class KPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/kpayment.php',
            'kpayment'
        );

        $this->app->singleton('kpayment', function ($app) {
            // Get settings from site settings, fallback to config/env
            $tranportalId = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_tranportal_id', config('kpayment.tranportal_id', ''));
            $tranportalPassword = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_tranportal_password', config('kpayment.tranportal_password', ''));
            $resourceKey = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_resource_key', config('kpayment.resource_key', ''));
            $baseUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_base_url', config('kpayment.base_url', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'));
            $testMode = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_test_mode', config('kpayment.test_mode', true));
            $responseUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_response_url', config('kpayment.response_url', ''));
            $errorUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_error_url', config('kpayment.error_url', ''));
            $currency = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_currency', config('kpayment.currency', '414'));
            $language = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_language', config('kpayment.language', 'EN'));
            $kfastEnabled = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_kfast_enabled', config('kpayment.kfast_enabled', false));
            $applePayEnabled = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_apple_pay_enabled', config('kpayment.apple_pay_enabled', false));
            
            // Convert test mode to boolean
            $testMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN);
            $kfastEnabled = filter_var($kfastEnabled, FILTER_VALIDATE_BOOLEAN);
            $applePayEnabled = filter_var($applePayEnabled, FILTER_VALIDATE_BOOLEAN);
            
            return new KnetService(
                $tranportalId,
                $tranportalPassword,
                $resourceKey,
                $baseUrl,
                $testMode,
                $responseUrl,
                $errorUrl,
                $currency,
                $language,
                $kfastEnabled,
                $applePayEnabled
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/kpayment.php' => config_path('kpayment.php'),
        ], 'kpayment-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'kpayment-migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'kpayment');
    }
}

