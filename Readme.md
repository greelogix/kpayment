# KNET Payment Laravel Package

A production-ready Laravel package for KNET payment gateway integration with support for standard payments, KFAST, Apple Pay, and refunds.

**✨ Professional Package Structure** - Follows Laravel package best practices with proper PSR-4 autoloading and standard directory structure.

**🚀 Production-Ready & Self-Contained** - This package includes everything you need out of the box:
- ✅ Complete payment flow (initiation, processing, response handling)
- ✅ Built-in payment form view (auto-submits to KNET)
- ✅ Success and error pages with professional UI
- ✅ Admin panel for settings and payment method management
- ✅ Automatic route registration (no manual setup needed)
- ✅ Comprehensive error handling and logging
- ✅ All base cases handled (no custom code required)
- ✅ Database-first configuration (manage via admin panel)
- ✅ Event system for payment status updates
- ✅ Refund processing support

**No customization required** - Install, configure, and use. Override views/layouts only if you want to match your design.

## Features

- ✅ Complete KNET Payment Gateway integration
- ✅ Form-based payment processing with redirects
- ✅ Payment response validation with hash verification
- ✅ Refund processing support
- ✅ KFAST (KNET Fast Payment) support
- ✅ Apple Pay integration support
- ✅ Admin panel for settings management
- ✅ Site settings-driven configuration (database-first approach)
- ✅ Payment method management
- ✅ Laravel 10.x, 11.x, and 12.x compatible
- ✅ Auto-discovery enabled
- ✅ Comprehensive error handling
- ✅ Payment status tracking
- ✅ Database models with relationships

## Requirements

- PHP >= 8.1
- Laravel 10.x, 11.x, or 12.x
- Composer
- KNET Merchant Account (for production - Tranportal ID, Password, Resource Key from your acquiring bank)

## Installation

### Step 1: Add Package Repository to composer.json

**IMPORTANT:** You must add the repository to `composer.json` BEFORE running `composer require`.

Open `composer.json` in your Laravel project root and add the repository:

**For SSH (recommended):**
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:greelogix/kpayment.git"
        }
    ]
}
```

**For HTTPS:**
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/greelogix/kpayment.git"
        }
    ]
}
```

**If using private repository, configure authentication:**

For SSH: Ensure your SSH key is added to GitHub.

For HTTPS with token:
```bash
composer config github-oauth.github.com your_token_here
```

### Step 2: Install Package

```bash
composer require greelogix/kpayment-laravel:dev-main
```

The package will be automatically discovered by Laravel (auto-discovery is enabled).

**Note:** If you get "package not found" error:
- Ensure the repository is added to `composer.json` first
- Save `composer.json` after adding the repository
- For private repos, ensure authentication is configured
- Run `composer update` if needed

### Step 3: Publish All Vendor Assets

```bash
php artisan vendor:publish --all
```

This will publish:
- `config/kpayment.php` → `config/kpayment.php`
- All views (admin + payment pages) → `resources/views/vendor/kpayment/`
- All migrations → `database/migrations/`
- All language files → `lang/vendor/kpayment/`

### Step 4: Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `kpayment_site_settings` - Stores configuration settings
- `kpayment_payments` - Stores payment transactions
- `kpayment_payment_methods` - Stores available payment methods

### Step 5: Run Seeders

```bash
# Dump autoload (ensures seeders are discoverable)
composer dump-autoload

# Seed default settings
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"

# Seed payment methods
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder"
```

**What gets seeded:**

**Default Settings:**
- Tranportal ID (empty - not required for testing)
- Tranportal Password (empty - not required for testing)
- Resource Key (empty - not required for testing)
- Base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm` (test environment)
- Test Mode: enabled
- Currency: 414 (KWD)
- Language: EN
- KFAST: disabled
- Apple Pay: disabled

**Payment Methods:**
- KNET Card
- Visa
- Mastercard
- Apple Pay (disabled by default)
- KFAST (disabled by default)

**Note:** Settings will also be automatically created when you first visit the admin settings page at `/admin/kpayment/settings`.

### Step 6: Configure Settings

**⚠️ Important: All KNET settings are managed through the admin panel, not directly from `.env`!**

1. **Ensure authentication is configured** (admin routes require `auth` middleware)

2. **Visit the admin settings page:**
   ```
   /admin/kpayment/settings
   ```

3. **Configure your settings:**

   **For Testing:**
   - Test Mode: Yes (enabled)
   - Base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
   - Credentials: Leave empty (not required for testing)

   **For Production:**
   - Test Mode: No (disabled)
   - Base URL: `https://www.kpay.com.kw/kpg/PaymentHTTP.htm`
   - Tranportal ID: Provided by your acquiring bank (REQUIRED)
   - Tranportal Password: Provided by your acquiring bank (REQUIRED)
   - Resource Key: Provided by your acquiring bank (REQUIRED)
   - Response URL: Where KNET redirects after payment (e.g., `https://yoursite.com/payment/success`)
   - Error URL: Where KNET redirects on errors (e.g., `https://yoursite.com/payment/error`)
   - Currency: Currency code (414 = KWD, 840 = USD, 682 = SAR)
   - Language: EN or AR
   - KFAST Enabled: Enable KFAST support (if needed)
   - Apple Pay Enabled: Enable Apple Pay support (if needed)
   - Apple Pay Certificate: Apple Pay certificate (if enabled)

4. **Click "Save Settings"**

5. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Step 7: Verify Installation

1. **Clear all caches:**
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Check routes are registered:**
   ```bash
   php artisan route:list | grep kpayment
   ```

   You should see:
   - `POST /kpayment/response` - Payment response handler
   - `GET /kpayment/response` - Payment response handler (GET)
   - `GET /payment/success` - Payment success page
   - `GET /payment/error` - Payment error page
   - `GET /admin/kpayment/settings` - Admin settings page
   - `POST /admin/kpayment/settings` - Save settings
   - `GET /admin/kpayment/payment-methods` - Payment methods management

3. **Visit admin settings page:**
   ```
   http://your-app.test/admin/kpayment/settings
   ```
   
   **If page doesn't load:**
   - Ensure you're logged in (admin routes require `auth` middleware)
   - Check `storage/logs/laravel.log` for errors
   - Run `php artisan package:discover`

4. **Check database tables:**
   ```bash
   php artisan tinker
   >>> \Greelogix\KPayment\Models\SiteSetting::count()
   >>> \Greelogix\KPayment\Models\PaymentMethod::count()
   ```

## Quick Start (Complete Installation)

For a complete installation in one go:

```bash
# 1. Add repository to composer.json (see Step 1 above)

# 2. Install package
composer require greelogix/kpayment-laravel:dev-main

# 3. Dump autoload
composer dump-autoload

# 4. Publish all vendor assets
php artisan vendor:publish --all

# 5. Run migrations
php artisan migrate

# 6. Seed settings
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"

# 7. Seed payment methods
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder"

# 8. Clear all caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

**That's it!** The package is now installed and ready to use.

**Important Notes:**
- **Routes are automatically loaded** - No need to publish routes. They're available immediately after installation.
- **Views are automatically loaded** - Views are accessible as `kpayment::admin.settings.index`. No need to publish unless you want to customize them.
- **Language files are automatically loaded** - Translations are accessible as `__('kpayment.key')`. No need to publish unless you want to customize them.
- **Payment pages** - Success and error pages are available at `/payment/success` and `/payment/error`
- **Payment form view** - Included at `kpayment::payment.form` - ready to use, no customization needed
- **All base cases handled** - The package handles all payment flows, errors, and edge cases automatically
- **Production-ready** - No additional code required from your project, works out of the box

## Example Setup

### Basic Payment Flow

1. **Show payment methods to user:**
   ```php
   use Greelogix\KPayment\Facades\KPayment;
   use Greelogix\KPayment\Models\PaymentMethod;

   // Get available payment methods
   $paymentMethods = PaymentMethod::activeForPlatform('web')->get();
   
   return view('checkout', compact('paymentMethods'));
   ```

2. **Generate payment form when user selects method:**
   ```php
   use Greelogix\KPayment\Facades\KPayment;

   $paymentData = KPayment::generatePaymentForm([
       'amount' => 100.000,
       'track_id' => 'ORDER-' . time(),
       'currency' => '414',
       'language' => 'EN',
       'payment_method_code' => 'VISA', // Optional: Pre-select method
   ]);

   // Use the built-in payment form view (included in package)
   return view('kpayment::payment.form', [
       'formUrl' => $paymentData['form_url'],
       'formData' => $paymentData['form_data'],
   ]);
   ```

   **Note:** The payment form view is included in the package at `kpayment::payment.form`. You can customize it by publishing views (`php artisan vendor:publish --all`) and editing `resources/views/vendor/kpayment/payment/form.blade.php`.

3. **Payment response is automatically handled:**
   - Success → Redirects to `/payment/success`
   - Error → Redirects to `/payment/error`
   - Payment details are available in session

### Listen to Payment Events

```php
// In app/Providers/EventServiceProvider.php
use Greelogix\KPayment\Events\PaymentStatusUpdated;

protected $listen = [
    PaymentStatusUpdated::class => [
        \App\Listeners\ProcessKnetPayment::class,
    ],
];
```

```php
// In app/Listeners/ProcessKnetPayment.php
namespace App\Listeners;

use Greelogix\KPayment\Events\PaymentStatusUpdated;

class ProcessKnetPayment
{
    public function handle(PaymentStatusUpdated $event)
    {
        $payment = $event->payment;
        
        if ($payment && $payment->isSuccessful()) {
            // Update order status
            // Send confirmation email
            // etc.
        } else {
            // Handle failed payment
        }
    }
}
```

### Process Refund

```php
use Greelogix\KPayment\Facades\KPayment;

try {
    $refundResult = KPayment::processRefund([
        'trans_id' => 'ORIGINAL_TRANSACTION_ID',
        'track_id' => 'REFUND-TRACK-ID',
        'amount' => 50.000,
    ]);

    if (isset($refundResult['result']) && $refundResult['result'] === 'CAPTURED') {
        // Refund successful
    }
} catch (\Greelogix\KPayment\Exceptions\KnetException $e) {
    // Handle error
}
```

### Using Models

```php
use Greelogix\KPayment\Facades\KPayment;
use Greelogix\KPayment\Models\KnetPayment;
use Greelogix\KPayment\Models\PaymentMethod;

// Get payment by track ID
$payment = KPayment::getPaymentByTrackId('ORDER-12345');

if ($payment && $payment->isSuccessful()) {
    // Payment successful
}

// Query payments
$successfulPayments = KnetPayment::successful()->get();
$failedPayments = KnetPayment::failed()->get();

// Get active payment methods
$webMethods = PaymentMethod::activeForPlatform('web')->get();
$iosMethods = PaymentMethod::activeForPlatform('ios')->get();
```

## Configuration Priority

The package uses a **database-first configuration approach**:

1. **Primary Source:** Values from `kpayment_site_settings` table (managed via `/admin/kpayment/settings`)
2. **Fallback:** `config/kpayment.php` values (if published)
3. **Last Fallback:** `.env` values (not recommended)

**Best Practice:** Manage all settings through the admin panel for production.

## Admin Panel

### Access Admin Routes

- **Settings:** `/admin/kpayment/settings` - Configure all KNET settings
- **Payment Methods:** `/admin/kpayment/payment-methods` - Manage payment methods

**Note:** Admin routes are protected with `auth` middleware. Ensure you have authentication configured in your Laravel application.

### Payment Methods Management

- **View Methods:** View all payment methods with their status and platform availability
- **Platform Activation:** Enable/disable payment methods for iOS, Android, and Web
- **Toggle Status:** Activate or deactivate payment methods

## Payment Response Handling

### Response Routes

The package automatically registers response routes:
- `POST /kpayment/response` (route name: `kpayment.response`)
- `GET /kpayment/response` (route name: `kpayment.response.get`)

These routes are **CSRF exempt** and handle payment responses from KNET.

### Success and Error Pages

The package includes built-in success and error pages:
- `GET /payment/success` (route name: `kpayment.success`)
- `GET /payment/error` (route name: `kpayment.error`)

These pages display payment details and are automatically used by the ResponseController.

### Response Parameters

KNET returns the following parameters:
- `paymentid` - Payment ID
- `trackid` - Track ID (your order ID)
- `result` - Result code (CAPTURED, NOT CAPTURED, etc.)
- `auth` - Authorization code
- `ref` - Reference number
- `tranid` - Transaction ID
- `postdate` - Post date
- `udf1` to `udf5` - User defined fields
- `hash` - Response hash for validation

### Response Events

The package fires the following event when payment status is updated:

```php
\Greelogix\KPayment\Events\PaymentStatusUpdated
```

## Testing

### Test Mode

**Important:** KNET test environment does NOT require any credentials or API keys for testing.

1. Set **Test Mode** to `Yes` in admin panel (`/admin/kpayment/settings`)
2. **Leave credentials empty** (Tranportal ID, Password, and Resource Key can be empty for testing)
3. Use test base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
4. You can test the payment flow without any credentials

### Test Cards

Refer to your acquiring bank for test card numbers. Test cards typically have:
- Expiration date: Future date (e.g., 12/2025)
- CVV: Any 3 digits

## Troubleshooting

### Routes Not Working / Admin Pages Not Showing

1. **Check package is discovered:**
   ```bash
   php artisan package:discover
   ```

2. **Clear all caches:**
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Verify authentication is configured** (admin routes require `auth` middleware)

4. **Check routes are registered:**
   ```bash
   php artisan route:list | grep kpayment
   ```

### Migration Issues

#### "Nothing to migrate" Error

If you get "Nothing to migrate" when running `php artisan migrate`:

**You skipped the publish step!** You MUST publish assets first:

```bash
# Publish all assets
php artisan vendor:publish --all

# Then run migrations
php artisan migrate
```

**If migrations still not found after publishing:**

1. **Check migrations were published:**
   ```bash
   ls -la database/migrations/ | grep kpayment
   ```
   You should see 3 migration files:
   - `2024_01_01_000001_create_kpayment_site_settings_table.php`
   - `2024_01_01_000002_create_kpayment_payments_table.php`
   - `2024_01_01_000003_create_kpayment_payment_methods_table.php`

2. **If migrations don't exist, publish again:**
   ```bash
   php artisan vendor:publish --all --force
   ```

3. **Check package is installed:**
   ```bash
   composer dump-autoload
   php artisan package:discover
   ```

### Seeder Classes Not Found

If you get "Target class does not exist" error when running seeders:

1. **Regenerate autoload files:**
   ```bash
   composer dump-autoload
   ```

2. **Then try running the seeder again:**
   ```bash
   php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"
   ```

### Views Not Loading

1. **Clear view cache:**
   ```bash
   php artisan view:clear
   ```

2. **Verify views exist in package:**
   - Views are in `vendor/greelogix/kpayment-laravel/resources/views/`
   - They're loaded with namespace `kpayment`

3. **If you want to customize views, publish them:**
   ```bash
   php artisan vendor:publish --all
   ```
   This will copy views to `resources/views/vendor/kpayment/` where you can customize them.

4. **Check for view errors:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Then visit `/admin/kpayment/settings` and check for any view-related errors.

### Payment Response Not Received

1. Check response URL is correctly configured in admin panel
2. Verify response URL is accessible from internet (not localhost)
3. Check Laravel logs for errors
4. Ensure CSRF exemption is working for response route

### Hash Validation Failed

1. Verify resource key is correct
2. Check that all parameters are included in hash calculation
3. Ensure no parameters are modified before validation

### Package Not Discovered

If auto-discovery doesn't work, manually register in `config/app.php`:

```php
'providers' => [
    // ...
    Greelogix\KPayment\KPaymentServiceProvider::class,
],

'aliases' => [
    // ...
    'KPayment' => Greelogix\KPayment\Facades\KPayment::class,
],
```

## Production Checklist

- [ ] Set **Test Mode** to `No` in admin panel
- [ ] **Configure all credentials** (Tranportal ID, Password, Resource Key)
  - These are **REQUIRED** for production
- [ ] Use production credentials from your acquiring bank
- [ ] Set base URL to `https://www.kpay.com.kw/kpg/PaymentHTTP.htm`
- [ ] Configure response URL (must be publicly accessible)
- [ ] Configure error URL (must be publicly accessible)
- [ ] Test payment flow end-to-end
- [ ] Verify response handling works correctly
- [ ] Monitor payment logs
- [ ] Test refund functionality
- [ ] If using Apple Pay, configure certificate
- [ ] If using KFAST, ensure it's enabled

## Security

- Response validation uses SHA-256 hash verification
- All sensitive data stored encrypted in database
- Admin routes protected with authentication middleware
- CSRF protection enabled (response routes are exempt)
- Resource key never exposed in frontend

## Currency Codes

Common currency codes:
- `414` - Kuwaiti Dinar (KWD)
- `840` - US Dollar (USD)
- `682` - Saudi Riyal (SAR)
- `978` - Euro (EUR)
- `826` - British Pound (GBP)

## Language Codes

- `EN` - English
- `AR` - Arabic

## License

MIT

## Support

For issues and questions:

- Check the [KNET Integration Manual](https://www.knet.com.kw/)
- Review package documentation
- Contact: asad.ali@greelogix.com

## Changelog

### Version 1.0.0

- Initial release
- Complete KNET Payment Gateway integration
- Admin panel for settings management
- Payment response handling with validation
- Refund processing support
- KFAST support
- Apple Pay support
- Payment status tracking
- Payment method management
- Professional Laravel package structure
- Built-in success and error pages
