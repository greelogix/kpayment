<?php

namespace Greelogix\KPay;

use Illuminate\Support\ServiceProvider;
use Greelogix\KPay\Services\KPayService;

class KPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/kpay.php',
            'kpay'
        );

        $this->app->singleton('kpay', function ($app) {
            $config = $app['config']->get('kpay');
            $testMode = $config['test_mode'] ?? true;
            
            // Auto-generate response URLs if not provided
            $responseUrl = $config['response_url'] ?? '';
            $errorUrl = $config['error_url'] ?? '';
            
            // If URLs not provided, try to generate from app URL
            if (empty($responseUrl) || empty($errorUrl)) {
                $appUrl = $app['config']->get('app.url', '');
                if (!empty($appUrl)) {
                    $baseResponseUrl = rtrim($appUrl, '/') . '/kpay/response';
                    if (empty($responseUrl)) {
                        $responseUrl = $baseResponseUrl;
                    }
                    if (empty($errorUrl)) {
                        $errorUrl = $baseResponseUrl;
                    }
                }
            }
            
            return new KPayService(
                $config['tranportal_id'] ?? '',
                $config['tranportal_password'] ?? '',
                $config['resource_key'] ?? '',
                $config['base_url'] ?? '', // Empty = auto-detect based on test_mode
                $testMode,
                $responseUrl,
                $errorUrl,
                $config['currency'] ?? '414',
                $config['language'] ?? 'USA',
                $config['kfast_enabled'] ?? false,
                $config['apple_pay_enabled'] ?? false
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes (automatically loaded - no need to publish)
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load views (automatically loaded - accessible as 'kpay::view.name')
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'kpay');

        // Load translations (automatically loaded - accessible as __('kpay.key'))
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'kpay');

        // Publishable assets (optional - only if user wants to customize)
        $this->publishes([
            __DIR__ . '/../config/kpay.php' => config_path('kpay.php'),
        ], 'kpay-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'kpay-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/kpay'),
        ], 'kpay-views');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/kpay'),
        ], 'kpay-lang');

        // Publish all KPay package assets at once
        $this->publishes([
            __DIR__ . '/../config/kpay.php' => config_path('kpay.php'),
            __DIR__ . '/../database/migrations' => database_path('migrations'),
            __DIR__ . '/../resources/views' => resource_path('views/vendor/kpay'),
            __DIR__ . '/../lang' => lang_path('vendor/kpay'),
        ], 'kpay');
    }
}


