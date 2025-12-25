# KNET Payment Laravel Package

A production-ready Laravel package for KNET payment gateway integration with support for standard payments, KFAST, Apple Pay, and refunds.

**✨ Professional Package Structure** - Follows Laravel package best practices with proper PSR-4 autoloading and standard directory structure.

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

## Package Structure

This package follows the standard Laravel package structure:

```
greelogix-kpayment/
├── composer.json
├── LICENSE
├── README.md
├── CHANGELOG.md
│
├── config/
│   └── kpayment.php
│
├── routes/
│   └── web.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   └── views/
│
└── src/
    ├── KPaymentServiceProvider.php
    ├── Events/
    ├── Exceptions/
    ├── Facades/
    ├── Http/
    │   └── Controllers/
    ├── Models/
    └── Services/
```

## Installation

### Step 1: Install Package via Composer

#### Option A: Install from Git Repository

**IMPORTANT:** You must add the repository to `composer.json` BEFORE running `composer require`.

1. **Add repository to your Laravel project's `composer.json`:**

   Open `composer.json` in your Laravel project root and add the repository:

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

   Or if using HTTPS:

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

2. **If using private repository, configure authentication:**

   For SSH (recommended): Ensure your SSH key is added to GitHub.

   For HTTPS with token:
   ```bash
   composer config github-oauth.github.com your_token_here
   ```

3. **Install the package:**

   ```bash
   composer require greelogix/kpayment-laravel:dev-main
   ```

   The package will be automatically discovered by Laravel (auto-discovery is enabled).

   **Note:** If you get "package not found" error, make sure:
   - The repository URL is correct in `composer.json`
   - You've saved `composer.json` after adding the repository
   - For private repos, authentication is configured
   - Run `composer update` if needed

#### Option B: Install from Packagist (When Published)

```bash
composer require greelogix/kpayment-laravel
```

### Step 2: Publish and Run Migrations

**⚠️ CRITICAL:** You **MUST** publish migrations first before running `php artisan migrate`. If you skip this step, you'll get "Nothing to migrate" error.

```bash
# Step 1: Publish migrations (REQUIRED - do not skip this!)
php artisan vendor:publish --tag=kpayment-migrations

# Step 2: Run migrations
php artisan migrate
```

This will create the following tables:
- `kpayment_site_settings` - Stores configuration settings
- `kpayment_payments` - Stores payment transactions
- `kpayment_payment_methods` - Stores available payment methods

**Why publish first?** While migrations can be auto-loaded from the package, publishing them ensures they're properly registered in your application's migration directory and allows you to customize them if needed. This is the recommended approach for production-ready packages.

### Step 3: Seed Default Settings

Seed the default KNET settings:

```bash
# If seeder class not found, run this first:
composer dump-autoload

# Then run the seeder:
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"
```

This will populate default KNET settings with test values:
- Tranportal ID (empty - not required for testing)
- Tranportal Password (empty - not required for testing)
- Resource Key (empty - not required for testing)
- Base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm` (test environment)
- Test Mode: enabled
- Currency: 414 (KWD)
- Language: EN
- KFAST: disabled
- Apple Pay: disabled

**Note:** 
- **For Testing:** KNET test environment does NOT require any credentials. You can test payments without configuring these fields.
- **For Production:** You must configure all credentials provided by your acquiring bank.
- Settings will also be automatically created when you first visit the admin settings page at `/admin/kpayment/settings`.

### Step 4: Seed Payment Methods

Seed the default payment methods:

```bash
# If seeder class not found, run this first:
composer dump-autoload

# Then run the seeder:
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder"
```

This will create the following payment methods:
- KNET Card
- Visa
- Mastercard
- Apple Pay (disabled by default)
- KFAST (disabled by default)

**Alternative:** You can also seed payment methods from the admin panel at `/admin/kpayment/payment-methods` by clicking "Seed Default Methods".

### Step 5: Configure Settings (Production)

**⚠️ Important: All KNET settings are managed through the admin panel, not directly from `.env`!**

1. **Ensure authentication is configured** (admin routes require `auth` middleware)

2. **Visit the admin settings page:**
   ```
   /admin/kpayment/settings
   ```

3. **Configure your production settings:**
   - **Tranportal ID:** Provided by your acquiring bank (REQUIRED for production)
   - **Tranportal Password:** Provided by your acquiring bank (REQUIRED for production)
   - **Resource Key:** Provided by your acquiring bank (REQUIRED for production)
   - **Base URL:** 
     - Test: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
     - Production: `https://www.kpay.com.kw/kpg/PaymentHTTP.htm`
   - **Test Mode:** Yes for testing (no credentials needed), No for production (credentials required)
   - **Response URL:** Where KNET redirects after payment (e.g., `https://yoursite.com/kpayment/response`)
   - **Error URL:** Where KNET redirects on errors (e.g., `https://yoursite.com/kpayment/error`)
   - **Currency:** Currency code (414 = KWD, 840 = USD, 682 = SAR)
   - **Language:** EN or AR
   - **KFAST Enabled:** Enable KFAST support
   - **Apple Pay Enabled:** Enable Apple Pay support
   - **Apple Pay Certificate:** Apple Pay certificate (if enabled)

4. **Click "Save Settings"**

5. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Step 6: Verify Installation

**Routes and views are automatically loaded from the package** - no need to publish them unless you want to customize.

1. **Clear all caches (important after installation):**
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
   - `GET /admin/kpayment/settings` - Admin settings page
   - `POST /admin/kpayment/settings` - Save settings
   - `GET /admin/kpayment/payment-methods` - Payment methods management

   **If routes don't appear:**
   - Run `php artisan package:discover`
   - Check `vendor/greelogix/kpayment-laravel/src/KPaymentServiceProvider.php` exists
   - Verify package is in `composer.json`

3. **Visit admin settings page:**
   ```
   http://your-app.test/admin/kpayment/settings
   ```
   
   **If page doesn't load:**
   - Ensure you're logged in (admin routes require `auth` middleware)
   - Check `storage/logs/laravel.log` for errors
   - Verify views exist: `vendor/greelogix/kpayment-laravel/resources/views/admin/settings/index.blade.php`

4. **Check database tables:**
   ```bash
   php artisan tinker
   >>> \Greelogix\KPayment\Models\SiteSetting::count()
   >>> \Greelogix\KPayment\Models\PaymentMethod::count()
   ```

## Quick Start (Complete Installation)

For a complete installation in one go, follow these steps:

### Step 1: Add Repository to composer.json

Open `composer.json` and add the repository section:

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

### Step 2: Install and Setup

```bash
# 1. Install package (after adding repository to composer.json)
composer require greelogix/kpayment-laravel:dev-main

# 2. Dump autoload (ensures seeders are discoverable)
composer dump-autoload

# 3. Publish migrations
php artisan vendor:publish --tag=kpayment-migrations

# 4. Run migrations
php artisan migrate

# 5. Seed settings
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"

# 6. Seed payment methods
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder"

# 7. Clear cache
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

**That's it!** The package is now installed and ready to use. No code changes needed in the package itself.

**Important Notes:**
- **Routes are automatically loaded** - No need to publish routes. They're available immediately after installation.
- **Views are automatically loaded** - Views are accessible as `kpayment::admin.settings.index`. No need to publish unless you want to customize them.
- **To customize views**, run: `php artisan vendor:publish --tag=kpayment-views`

**Troubleshooting Installation:**
- If `composer require` fails with "package not found", ensure the repository is added to `composer.json` first
- Save `composer.json` after adding the repository
- For private repos, ensure SSH key is added to GitHub or configure token authentication
- If seeder classes are not found, run `composer dump-autoload` after installation
- If routes/views don't work, run `php artisan package:discover` and clear all caches

## Configuration Priority

The package uses a **database-first configuration approach**:

1. **Primary Source:** Values from `kpayment_site_settings` table (managed via `/admin/kpayment/settings`)
2. **Fallback:** `config/kpayment.php` values
3. **Last Fallback:** `.env` values (e.g., `KPAYMENT_TRANPORTAL_ID`, `KPAYMENT_BASE_URL`)

**Best Practice:** Manage all settings through the admin panel for production. Use `.env` only as a backup for local/test environments.

### Optional: Publish Config File

If you want to customize the config file:

```bash
php artisan vendor:publish --tag=kpayment-config
```

This will publish `config/kpayment.php` to your app's config directory.

### Optional: Publish Views

If you want to customize the admin views:

```bash
php artisan vendor:publish --tag=kpayment-views
```

This will publish views to `resources/views/vendor/kpayment`.

## Usage

### Get Available Payment Methods

```php
use Greelogix\KPayment\Facades\KPayment;

// Get all active payment methods for web
$paymentMethods = KPayment::getPaymentMethods('web');

// Get payment methods for iOS
$iosMethods = KPayment::getPaymentMethods('ios');

// Get payment methods for Android
$androidMethods = KPayment::getPaymentMethods('android');
```

### Basic Payment Initiation

KNET uses a form-based redirect approach. Generate the payment form data and redirect the user:

```php
use Greelogix\KPayment\Facades\KPayment;

try {
    $paymentData = KPayment::generatePaymentForm([
        'amount' => 100.000, // Amount with 3 decimal places
        'track_id' => 'ORDER-12345', // Your order/tracking ID
        'currency' => '414', // 414 = KWD
        'language' => 'EN', // EN or AR
        'payment_method_code' => 'VISA', // Optional: Pre-select payment method (KNET, VISA, MASTERCARD, APPLE_PAY, KFAST)
        'response_url' => url('/kpayment/response'), // Optional, uses default from settings
        'error_url' => url('/payment/error'), // Optional, uses default from settings - create this route in your app
        'udf1' => 'Custom data 1', // Optional UDF fields
        'udf2' => 'Custom data 2',
        'udf3' => 'Custom data 3',
        'udf4' => 'Custom data 4',
        'udf5' => 'Custom data 5',
    ]);

    // Store payment ID for reference
    $paymentId = $paymentData['payment_id'];
    $trackId = $paymentData['track_id'];

    // Create a form and auto-submit to KNET
    return view('payment.knet-form', [
        'formUrl' => $paymentData['form_url'],
        'formData' => $paymentData['form_data'],
    ]);
} catch (\Greelogix\KPayment\Exceptions\KnetException $e) {
    // Handle error
    return back()->with('error', $e->getMessage());
}
```

### Payment Form View

Create a view file `resources/views/payment/knet-form.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to KNET...</title>
</head>
<body>
    <form id="knetForm" method="POST" action="{{ $formUrl }}">
        @foreach($formData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
    <script>
        document.getElementById('knetForm').submit();
    </script>
    <p>Redirecting to KNET Payment Gateway...</p>
</body>
</html>
```

### Handle Payment Response

The package automatically handles responses at `/kpayment/response`. The response controller will redirect to success/error URLs based on payment status.

You can customize the success/error handling by:

1. **Creating your own success/error routes** in your application
2. **Listening to the `PaymentStatusUpdated` event** to handle payment status changes
3. **Customizing the ResponseController** (publish and modify if needed)

**Note:** The ResponseController uses `udf1` for success URL and `udf2` for error URL if provided, otherwise it will try to redirect to routes that you need to create in your application.

```php
// In your EventServiceProvider (app/Providers/EventServiceProvider.php)
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Greelogix\KPayment\Events\PaymentStatusUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentStatusUpdated::class => [
            // Your listeners here
            \App\Listeners\ProcessKnetPayment::class,
        ],
    ];
}

// Example Listener (app/Listeners/ProcessKnetPayment.php)
namespace App\Listeners;

use Greelogix\KPayment\Events\PaymentStatusUpdated;

class ProcessKnetPayment
{
    public function handle(PaymentStatusUpdated $event)
    {
        $payment = $event->payment;
        
        // Always check if payment exists
        if ($payment && $payment->isSuccessful()) {
            // Handle successful payment
            // Update order status, send confirmation email, etc.
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
        'amount' => 50.000, // Refund amount
    ]);

    // Handle refund result
    if (isset($refundResult['result']) && $refundResult['result'] === 'CAPTURED') {
        // Refund successful
    }
} catch (\Greelogix\KPayment\Exceptions\KnetException $e) {
    // Handle error
}
```

### Payment Method Selection Flow

Similar to MyFatoorah, you can show payment methods to users and redirect to KNET with a selected method:

```php
// In your payment page controller (app/Http/Controllers/PaymentController.php)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Greelogix\KPayment\Facades\KPayment;
use Greelogix\KPayment\Models\PaymentMethod;

class PaymentController extends Controller
{
    public function showPaymentMethods()
    {
        // Get available payment methods for current platform
        $platform = request()->is('mobile/*') ? 'ios' : 'web';
        $paymentMethods = PaymentMethod::activeForPlatform($platform)->get();
        
        return view('payment.methods', compact('paymentMethods'));
    }

    // When user selects a payment method
    public function processPayment(Request $request)
    {
        $request->validate([
            'payment_method_code' => 'required|exists:kpayment_payment_methods,code',
            'amount' => 'required|numeric',
        ]);

        $paymentData = KPayment::generatePaymentForm([
            'amount' => $request->amount,
            'track_id' => 'ORDER-' . time(),
            'payment_method_code' => $request->payment_method_code, // Pre-select method
            'response_url' => url('/kpayment/response'), // Package handles this automatically
            'error_url' => url('/payment/error'), // Create this route in your app
        ]);

        return view('payment.knet-form', [
            'formUrl' => $paymentData['form_url'],
            'formData' => $paymentData['form_data'],
        ]);
    }
}
```

### Using Models

```php
use Greelogix\KPayment\Facades\KPayment;
use Greelogix\KPayment\Models\KnetPayment;
use Greelogix\KPayment\Models\PaymentMethod;

// Get payment by track ID
$payment = KPayment::getPaymentByTrackId('ORDER-12345');

// Check payment status (always check if payment exists)
if ($payment && $payment->isSuccessful()) {
    // Payment successful
}

// Get payment by transaction ID
$payment = KPayment::getPaymentByTransId('TRANS123456');

// Always check if payment exists before using it
if ($payment) {
    // Use payment object
    if ($payment->isSuccessful()) {
        // Payment successful
    }
}

// Query payments
$successfulPayments = KnetPayment::successful()->get();
$failedPayments = KnetPayment::failed()->get();
$pendingPayments = KnetPayment::pending()->get();

// Always check if collection is not empty
if ($successfulPayments->isNotEmpty()) {
    foreach ($successfulPayments as $payment) {
        // Process successful payment
    }
}

// Get active payment methods for platform
$iosMethods = PaymentMethod::activeForPlatform('ios')->get();
$androidMethods = PaymentMethod::activeForPlatform('android')->get();
$webMethods = PaymentMethod::activeForPlatform('web')->get();
```

### Validate Response Manually

```php
use Greelogix\KPayment\Facades\KPayment;

$response = request()->all();

if (KPayment::validateResponse($response)) {
    // Response is valid, process it
    try {
        $payment = KPayment::processResponse($response);
        if ($payment && $payment->isSuccessful()) {
            // Payment successful
        }
    } catch (\Greelogix\KPayment\Exceptions\KnetException $e) {
        // Handle exception (invalid hash, payment not found, etc.)
        // Log error or handle invalid response
    }
} else {
    // Invalid response hash
    // Log error or handle invalid response
}
```

## Admin Panel

### Access Admin Routes

- **Settings:** `/admin/kpayment/settings` - Configure all KNET settings
- **Payment Methods:** `/admin/kpayment/payment-methods` - Manage payment methods

**Note:** Admin routes are protected with `auth` middleware. Ensure you have authentication configured in your Laravel application.

### Payment Methods Management

- **Seed Default Methods:** Click "Seed Default Methods" to populate KNET, Visa, Mastercard, Apple Pay, and KFAST
- **View Methods:** View all payment methods with their status and platform availability
- **Platform Activation:** Enable/disable payment methods for iOS, Android, and Web
- **Toggle Status:** Activate or deactivate payment methods

## Payment Response Handling

### Response URL

The package automatically registers response routes:
- `POST /kpayment/response` (route name: `kpayment.response`)
- `GET /kpayment/response` (route name: `kpayment.response.get`)

These routes are **CSRF exempt** and handle payment responses from KNET.

**Important:** You should create your own success and error routes in your application. The ResponseController will redirect to:
- Success URL: Uses `udf1` field if provided, or you need to create a `kpayment.success` route
- Error URL: Uses `udf2` field if provided, or you need to create a `kpayment.error` route

Example routes to add in your `routes/web.php`:

```php
Route::get('/payment/success', function () {
    return view('payment.success');
})->name('kpayment.success');

Route::get('/payment/error', function () {
    return view('payment.error');
})->name('kpayment.error');
```

### Response Parameters

KNET returns the following parameters in the response:

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

1. Set **Test Mode** to `true` in admin panel (`/admin/kpayment/settings`)
2. **Leave credentials empty** (Tranportal ID, Password, and Resource Key can be empty for testing)
3. Use test base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
4. You can test the payment flow without any credentials

### Test Cards

Refer to your acquiring bank for test card numbers. Test cards typically have:
- Expiration date: Future date (e.g., 12/2025)
- CVV: Any 3 digits

## Troubleshooting

### Settings Not Updating

```bash
php artisan config:clear
php artisan cache:clear
```

### Migration Issues

#### "Nothing to migrate" Error

If you get "Nothing to migrate" when running `php artisan migrate`:

**This means you skipped the publish step!** You MUST publish migrations first:

```bash
# Step 1: Publish migrations (REQUIRED!)
php artisan vendor:publish --tag=kpayment-migrations

# Step 2: Now run migrations
php artisan migrate
```

**Why this happens:** The package uses `loadMigrationsFrom()` which can work, but Laravel may not always detect them correctly. Publishing migrations ensures they're properly registered in your `database/migrations` directory.

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
   php artisan vendor:publish --tag=kpayment-migrations --force
   ```

3. **Check package is installed:**
   ```bash
   composer dump-autoload
   php artisan package:discover
   ```

4. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

**Rollback and re-run:**
```bash
php artisan migrate:rollback
php artisan migrate
```

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

**Note:** Seeders are autoloaded from the package. If you're using an older version, update the package first.

### Routes Not Working / Admin Pages Not Showing

**Important:** Routes and views are automatically loaded from the package - you don't need to publish them unless you want to customize them.

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
   
   You should see:
   - `GET|POST /kpayment/response`
   - `GET /admin/kpayment/settings`
   - `POST /admin/kpayment/settings`
   - `GET /admin/kpayment/payment-methods`

5. **If routes still not showing, manually register the service provider** in `config/app.php`:
   ```php
   'providers' => [
       // ...
       Greelogix\KPayment\KPaymentServiceProvider::class,
   ],
   ```

### Views Not Loading

**Views are automatically loaded from the package** and accessible as `kpayment::admin.settings.index`.

If views are not loading:

1. **Clear view cache:**
   ```bash
   php artisan view:clear
   ```

2. **Verify views exist in package:**
   - Views are in `vendor/greelogix/kpayment-laravel/resources/views/`
   - They're loaded with namespace `kpayment`

3. **If you want to customize views, publish them:**
   ```bash
   php artisan vendor:publish --tag=kpayment-views
   ```
   This will copy views to `resources/views/vendor/kpayment/` where you can customize them.

4. **Check for view errors:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Then visit `/admin/kpayment/settings` and check for any view-related errors.

## Production Checklist

- [ ] Set **Test Mode** to `false` in admin panel
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
