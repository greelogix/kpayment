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
            // Get settings from database only (no config/env fallbacks)
            $tranportalId = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_tranportal_id', '');
            $tranportalPassword = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_tranportal_password', '');
            $resourceKey = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_resource_key', '');
            $baseUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_base_url', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm');
            $testMode = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_test_mode', '1');
            $responseUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_response_url', '');
            $errorUrl = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_error_url', '');
            $currency = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_currency', '414');
            $language = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_language', 'EN');
            $kfastEnabled = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_kfast_enabled', '0');
            $applePayEnabled = \Greelogix\KPayment\Models\SiteSetting::getValue('kpayment_apple_pay_enabled', '0');
            
            // Convert to boolean
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

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/kpayment'),
        ], 'kpayment-views');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'kpayment');
    }
}


