# KNET Payment Laravel Package

A production-ready Laravel package for KNET payment gateway integration with support for standard payments, KFAST, Apple Pay, and refunds.

## Features

- ✅ Complete KNET Payment Gateway integration
- ✅ Form-based payment processing with redirects
- ✅ Payment response validation with hash verification
- ✅ Refund processing support
- ✅ KFAST (KNET Fast Payment) support
- ✅ Apple Pay integration support
- ✅ Admin panel for settings management
- ✅ Site settings-driven configuration (database-first approach)
- ✅ Laravel 10.x and 11.x compatible
- ✅ Auto-discovery enabled
- ✅ Comprehensive error handling
- ✅ Payment status tracking
- ✅ Database models with relationships

## Requirements

- PHP >= 8.1
- Laravel 10.x or 11.x
- Composer
- KNET Merchant Account (Tranportal ID, Password, Resource Key from your acquiring bank)

## Installation

### Step 1: Install via Composer

#### Option A: Install from Git Repository (Recommended)

1. **Add repository to your Laravel project's `composer.json`:**

   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "git@github.com:greelogix/kpayment.git"
           }
       ],
       "require": {
           "greelogix/kpayment-laravel": "dev-main"
       }
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
       ],
       "require": {
           "greelogix/kpayment-laravel": "dev-main"
       }
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

   Or if you've already added the repository to `composer.json`:

   ```bash
   composer install
   ```

#### Option B: Install from Packagist (When Published)

```bash
composer require greelogix/kpayment-laravel
```

### Step 2: Publish and Run Migrations

```bash
# Publish migrations
php artisan vendor:publish --tag=kpayment-migrations

# Run migrations
php artisan migrate
```

This will create the following tables:
- `kpayment_site_settings` - Stores configuration settings
- `kpayment_payments` - Stores payment transactions
- `kpayment_payment_methods` - Stores available payment methods

### Step 3: Seed Default Settings

```bash
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\DefaultSettingsSeeder"
```

This will populate default KNET settings:
- Tranportal ID (empty - to be configured)
- Tranportal Password (empty - to be configured)
- Resource Key (empty - to be configured)
- Base URL (test URL)
- Test Mode: enabled
- Currency: 414 (KWD)
- Language: EN
- KFAST: disabled
- Apple Pay: disabled

**Note:** Settings will also be automatically created when you first visit the admin settings page.

### Step 4: Configure Settings in Admin Panel

**⚠️ Important: All KNET settings are managed through the admin panel (site settings table), not directly from `.env`!**

1. **Ensure authentication is configured** (admin routes require `auth` middleware)

2. **Visit the admin settings page:**
   ```
   /admin/kpayment/settings
   ```

3. **Configure your settings:**
   - **Tranportal ID:** Provided by your acquiring bank
   - **Tranportal Password:** Provided by your acquiring bank
   - **Resource Key:** Provided by your acquiring bank
   - **Base URL:** 
     - Test: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
     - Production: `https://www.kpay.com.kw/kpg/PaymentHTTP.htm`
   - **Test Mode:** Yes for testing, No for production
   - **Response URL:** Where KNET redirects after payment (e.g., `https://yoursite.com/kpayment/response`)
   - **Error URL:** Where KNET redirects on errors (e.g., `https://yoursite.com/kpayment/error`)
   - **Currency:** Currency code (414 = KWD, 840 = USD, 682 = SAR)
   - **Language:** EN or AR
   - **KFAST Enabled:** Enable KFAST support
   - **Apple Pay Enabled:** Enable Apple Pay support
   - **Apple Pay Certificate:** Apple Pay certificate (if enabled)

4. **Click "Save Settings"**

**Note:** After changing settings, clear cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Seed Payment Methods

**Option A: Using Seeder**

```bash
php artisan db:seed --class="Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder"
```

**Option B: Using Admin Panel (Recommended)**

1. Visit `/admin/kpayment/payment-methods`
2. Click "Seed Default Methods" button
3. Default payment methods will be created (KNET, Visa, Mastercard, Apple Pay, KFAST)
4. You can then enable/disable methods and configure platform availability

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

The package automatically handles responses at `/kpayment/response`. The response controller will redirect to success/error URLs based on payment status. You can customize the success/error handling by:

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
use Illuminate\Http\Request;
use Greelogix\KPayment\Facades\KPayment;
use Greelogix\KPayment\Models\PaymentMethod;

// In your payment page controller
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
```

### Using Models

```php
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
- **Add/Edit Methods:** Manage payment methods manually
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

1. Set **Test Mode** to `true` in admin panel (`/admin/kpayment/settings`)
2. Use test credentials from your acquiring bank
3. Use test base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`

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

```bash
# Rollback and re-run migrations
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

## Production Checklist

- [ ] Set **Test Mode** to `false` in admin panel
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

