# KPay - KNET Payment Laravel Package

A lightweight Laravel package for KNET payment gateway integration. Simple payment service - no admin panels, no database settings management.

**✨ Core Payment Service** - Just payment processing, nothing extra.

## Features

- ✅ Initiate payment with KNET
- ✅ Get payment methods (standard KNET methods)
- ✅ Payment callback handling (automatic)
- ✅ Success and error pages
- ✅ Payment response validation with hash verification
- ✅ Refund processing support
- ✅ Transaction inquiry API (check incomplete orders)
- ✅ Event system for payment status updates
- ✅ Laravel 10.x, 11.x, and 12.x compatible
- ✅ Auto-discovery enabled
- ✅ Comprehensive error handling
- ✅ Payment status tracking
- ✅ KNET-compliant hash generation and validation

## Requirements

- PHP >= 8.1
- Laravel 10.x, 11.x, or 12.x
- Composer
- KNET Merchant Account (for production - Tranportal ID, Password, Resource Key from your acquiring bank)

## Quick Start

```bash
# 1. Add repository to composer.json (see Step 1 below)

# 2. Install package
composer require greelogix/kpay-laravel:dev-main

# 3. Publish assets
php artisan vendor:publish --tag=kpay

# 4. Run migrations (or configure to use existing table)
php artisan migrate

# 5. Configure .env (see Step 5 below)

# 6. Clear cache
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

**That's it!** The package is ready to use. No seeders, no admin setup needed.

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
            "url": "git@github.com:greelogix/kpay.git"
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
            "url": "https://github.com/greelogix/kpay.git"
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
composer require greelogix/kpay-laravel:dev-main
```

The package will be automatically discovered by Laravel (auto-discovery is enabled).

**Note:** If you get "package not found" error:
- Ensure the repository is added to `composer.json` first
- Save `composer.json` after adding the repository
- For private repos, ensure authentication is configured
- Run `composer update` if needed

### Step 3: Publish Package Assets

```bash
php artisan vendor:publish --tag=kpay
```

This will publish:
- `config/kpay.php` → `config/kpay.php`
- Payment views → `resources/views/vendor/kpay/`
- Migrations → `database/migrations/`
- Language files → `lang/vendor/kpay/`

### Step 4: Run Migrations (Optional)

**Option A: Create New Table (Default)**
```bash
php artisan migrate
```
This will create the `kpay_payments` table for payment tracking.

**Option B: Use Existing Table**
If you already have a `payments` or `transactions` table, configure it in `.env`:
```env
KPAY_PAYMENT_TABLE=payments
KPAY_CREATE_TABLE=false
```
Then skip the migration. Make sure your existing table has the required columns (see Configuration section below).

### Step 5: Configure Settings

The package automatically manages URLs based on `KPAY_TEST_MODE`. You only need to configure the essentials:

#### **For Development/Testing:**

```env
# Mode (controls everything automatically)
KPAY_TEST_MODE=true

# Your app URL (for auto-generating response URLs)
APP_URL=https://your-test-domain.com

# Credentials (optional for test mode)
KPAY_TRANPORTAL_ID=
KPAY_TRANPORTAL_PASSWORD=
KPAY_RESOURCE_KEY=
```

**What happens automatically:**
- Base URL → `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
- Response URL → `{APP_URL}/kpay/response`
- Error URL → `{APP_URL}/kpay/response`
- Credentials → Not required

#### **For Production:**

```env
# Mode (controls everything automatically)
KPAY_TEST_MODE=false

# Your app URL (for auto-generating response URLs)
APP_URL=https://yourdomain.com

# Credentials (REQUIRED for production)
KPAY_TRANPORTAL_ID=your_production_id
KPAY_TRANPORTAL_PASSWORD=your_production_password
KPAY_RESOURCE_KEY=your_production_resource_key
```

**What happens automatically:**
- Base URL → `https://www.kpay.com.kw/kpg/PaymentHTTP.htm`
- Response URL → `{APP_URL}/kpay/response`
- Error URL → `{APP_URL}/kpay/response`
- Credentials → Required (will throw error if missing)

#### **Optional Overrides (only if needed):**

If you need custom URLs, you can override:

```env
# Only set these if you need custom URLs
KPAY_BASE_URL=https://custom-knet-url.com
KPAY_RESPONSE_URL=https://custom-response-url.com
KPAY_ERROR_URL=https://custom-error-url.com
```

**Note:** The package automatically generates response URLs from `APP_URL`. Make sure `APP_URL` is set correctly in your `.env` file.

### Step 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 7: Verify Installation

```bash
php artisan route:list | grep kpay
```

You should see:
- `POST /kpay/response` - Payment response handler
- `GET /kpay/response` - Payment response handler (GET)
- `GET /payment/success` - Payment success page
- `GET /payment/error` - Payment error page

## Usage

### Get Payment Methods

```php
use Greelogix\KPay\Facades\KPay;

// Get payment methods (standard KNET methods)
$paymentMethods = KPay::getPaymentMethods('web');

// Returns array of payment methods:
// [
//     ['code' => 'KNET', 'name' => 'KNET Card', 'platform' => ['web', 'ios', 'android']],
//     ['code' => 'VISA', 'name' => 'Visa', 'platform' => ['web', 'ios', 'android']],
//     ['code' => 'MASTERCARD', 'name' => 'Mastercard', 'platform' => ['web', 'ios', 'android']],
//     // ... KFAST and Apple Pay if enabled
// ]
```

**Note:** `getPaymentMethods()` returns standard KNET payment methods (KNET, Visa, Mastercard, plus KFAST/Apple Pay if enabled).

### Initiate Payment

#### **For Web Forms (Blade Views)**

```php
use Greelogix\KPay\Facades\KPay;

$paymentData = KPay::generatePaymentForm([
    'amount' => 100.000,              // Amount with 3 decimal places
    'track_id' => 'ORDER-12345',      // Your order/tracking ID
    'currency' => '414',               // 414 = KWD, 840 = USD, etc.
    'language' => 'EN',                // EN or AR
    'payment_method_code' => 'VISA',  // Optional: Pre-select method
    'udf1' => 'ORDER-12345',          // Optional: Store order ID
    'udf2' => 'USER-123',             // Optional: Store user ID
    // ... udf3, udf4, udf5
]);

// Returns:
// [
//     'form_url' => 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm',
//     'form_data' => [...], // Form fields to submit
//     'payment_id' => 1,
//     'track_id' => 'ORDER-12345'
// ]

// Use the built-in payment form view
return view('kpay::payment.form', [
        'formUrl' => $paymentData['form_url'],
        'formData' => $paymentData['form_data'],
    ]);
```

#### **For API Responses (JSON)**

**Option 1: Return URL + Form Data (Frontend submits form)**

```php
use Greelogix\KPay\Facades\KPay;

// Generate payment URL for API
$paymentData = KPay::generatePaymentUrl([
    'amount' => 100.000,
    'track_id' => 'ORDER-12345',
    'currency' => '414',
    'language' => 'EN',
    'udf1' => 'ORDER-12345',
]);

// Returns API-friendly structure:
// [
//     'payment_url' => 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm',
//     'payment_id' => 1,
//     'track_id' => 'ORDER-12345',
//     'form_data' => [
//         'id' => 'MERCHANT123',
//         'password' => 'PASS456',
//         'action' => '1',
//         'langid' => 'EN',
//         'currencycode' => '414',
//         'amt' => '100.000',
//         'trackid' => 'ORDER-12345',
//         'responseURL' => 'https://yoursite.com/kpay/response',
//         'errorURL' => 'https://yoursite.com/kpay/response',
//         'udf1' => 'ORDER-12345',
//         'hash' => 'A1B2C3D4E5F6...',
//     ],
//     'method' => 'POST',
// ]

// Return as JSON response
return response()->json([
    'success' => true,
    'data' => $paymentData,
]);
```

**Option 2: Return Redirect URL (Auto-submits form - Recommended for Mobile Apps)**

```php
use Greelogix\KPay\Facades\KPay;

// Generate redirect URL that auto-submits form
$paymentData = KPay::generatePaymentRedirectUrl([
    'amount' => 100.000,
    'track_id' => 'ORDER-12345',
    'currency' => '414',
    'language' => 'EN',
    'udf1' => 'ORDER-12345',
]);

// Returns:
// [
//     'redirect_url' => 'https://yoursite.com/kpay/redirect/1',  // Use this URL!
//     'payment_url' => 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm',
//     'payment_id' => 1,
//     'track_id' => 'ORDER-12345',
//     'form_data' => [...],  // For reference
//     'method' => 'POST',
// ]

// Return as JSON response
return response()->json([
    'success' => true,
    'data' => [
        'payment_url' => $paymentData['redirect_url'],  // Use redirect_url
        'payment_id' => $paymentData['payment_id'],
        'track_id' => $paymentData['track_id'],
    ],
]);
```

**Important:** 
- `redirect_url` can be opened directly in browser/mobile app - it auto-submits the form
- `payment_url` is the KNET gateway URL (requires POST with form data)
- For mobile apps or direct links, use `redirect_url`
- For web apps with custom form handling, use `payment_url` + `form_data`

**Example API Controller:**

```php
// app/Http/Controllers/Api/PaymentController.php
use Greelogix\KPay\Facades\KPay;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.001',
            'track_id' => 'required|string',
            'currency' => 'sometimes|string',
            'language' => 'sometimes|string|in:EN,AR',
        ]);

        try {
            $paymentData = KPay::generatePaymentUrl([
                'amount' => $request->amount,
                'track_id' => $request->track_id,
                'currency' => $request->currency ?? '414',
                'language' => $request->language ?? 'EN',
                'udf1' => $request->track_id, // Store order ID
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment URL generated successfully',
                'data' => $paymentData,
            ]);
        } catch (\Greelogix\KPay\Exceptions\KPayException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Payment Request Details

#### **Payment Gateway URL**

**Test Environment:**
```
https://kpaytest.com.kw/kpg/PaymentHTTP.htm
```

**Production Environment:**
```
https://www.kpay.com.kw/kpg/PaymentHTTP.htm
```

#### **HTTP Request Method**
- **Method:** `POST`
- **Content-Type:** `application/x-www-form-urlencoded`

#### **Complete Request Payload Structure**

The `generatePaymentForm()` method creates the following payload that gets submitted to KNET:

```php
[
    // Required Parameters
    'id' => 'YOUR_TRANPORTAL_ID',              // Tranportal ID from your bank
    'password' => 'YOUR_TRANPORTAL_PASSWORD',   // Tranportal password
    'action' => '1',                            // 1 = Purchase transaction
    'langid' => 'EN',                           // Language: EN or AR
    'currencycode' => '414',                    // Currency code (414 = KWD)
    'amt' => '100.000',                         // Amount with 3 decimal places
    'trackid' => 'ORDER-12345',                 // Unique track ID (your order ID)
    'responseURL' => 'https://yoursite.com/kpay/response',  // Response callback URL
    'errorURL' => 'https://yoursite.com/kpay/response',     // Error callback URL
    
    // Optional UDF Fields (User Defined Fields)
    'udf1' => 'ORDER-12345',                   // Optional: Store order ID
    'udf2' => 'USER-123',                      // Optional: Store user ID
    'udf3' => '',                              // Optional: Custom data
    'udf4' => '',                              // Optional: Custom data
    'udf5' => '',                              // Optional: Custom data
    
    // Security Hash (automatically generated)
    'hash' => 'A1B2C3D4E5F6...',               // SHA-256 hash for validation
]
```

#### **Hash Generation**

The hash is automatically generated using the following algorithm (per KNET specification):

1. **Hash String Format:** `resource_key + sorted_parameter_values`
2. **Sorting:** Parameters sorted alphabetically by key name (case-insensitive)
3. **Exclusions:** Empty values and the `hash` field itself are excluded
4. **Algorithm:** SHA-256
5. **Output:** Uppercase hexadecimal string

**Example Hash Calculation:**
```php
// Parameters (before sorting):
[
    'id' => 'MERCHANT123',
    'password' => 'PASS456',
    'action' => '1',
    'amt' => '100.000',
    'trackid' => 'ORDER-12345',
    'currencycode' => '414',
    'langid' => 'EN',
    'responseURL' => 'https://yoursite.com/kpay/response',
    'errorURL' => 'https://yoursite.com/kpay/response',
]

// Sorted by key (alphabetically):
// action, amt, currencycode, errorURL, id, langid, password, responseURL, trackid

// Hash String:
$hashString = $resourceKey . '1' . '100.000' . '414' . 'https://yoursite.com/kpay/response' . 'MERCHANT123' . 'EN' . 'PASS456' . 'https://yoursite.com/kpay/response' . 'ORDER-12345';

// Final Hash:
$hash = strtoupper(hash('sha256', $hashString));
```

#### **Example Complete Request**

**Using the Package:**
```php
$paymentData = KPay::generatePaymentForm([
    'amount' => 100.000,
    'track_id' => 'ORDER-12345',
    'currency' => '414',
    'language' => 'EN',
    'udf1' => 'ORDER-12345',
]);

// $paymentData['form_data'] contains:
[
    'id' => 'MERCHANT123',
    'password' => 'PASS456',
    'action' => '1',
    'langid' => 'EN',
    'currencycode' => '414',
    'amt' => '100.000',
    'trackid' => 'ORDER-12345',
    'responseURL' => 'https://yoursite.com/kpay/response',
    'errorURL' => 'https://yoursite.com/kpay/response',
    'udf1' => 'ORDER-12345',
    'hash' => 'A1B2C3D4E5F6789012345678901234567890ABCDEF1234567890ABCDEF12',
]
```

**Raw HTTP POST Request (what gets sent to KNET):**
```
POST https://kpaytest.com.kw/kpg/PaymentHTTP.htm
Content-Type: application/x-www-form-urlencoded

id=MERCHANT123&password=PASS456&action=1&langid=EN&currencycode=414&amt=100.000&trackid=ORDER-12345&responseURL=https%3A%2F%2Fyoursite.com%2Fkpay%2Fresponse&errorURL=https%3A%2F%2Fyoursite.com%2Fkpay%2Fresponse&udf1=ORDER-12345&hash=A1B2C3D4E5F6789012345678901234567890ABCDEF1234567890ABCDEF12
```

#### **Return Value Structure**

```php
[
    'form_url' => 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm',  // KNET payment gateway URL
    'form_data' => [                                               // Complete form payload
        'id' => '...',
        'password' => '...',
        'action' => '1',
        // ... all parameters
        'hash' => '...',
    ],
    'payment_id' => 1,                                            // Database payment record ID
    'track_id' => 'ORDER-12345',                                  // Track ID used
]
```

#### **Important Notes**

1. **Amount Format:** Must have exactly 3 decimal places (e.g., `100.000`, not `100` or `100.00`)
2. **Track ID:** Must be unique for each transaction (used to match responses)
3. **Hash:** Automatically generated - never manually create it
4. **URLs:** Response and error URLs must be publicly accessible (HTTPS recommended)
5. **Test Mode:** In test mode, credentials can be empty (KNET test environment doesn't require them)
6. **Production:** All credentials (id, password, resource_key) are REQUIRED in production

### Payment Callback (Automatic)

The package automatically handles payment callbacks at `/kpay/response`. When payment completes:

1. Payment is validated and processed
2. `PaymentStatusUpdated` event is fired
3. User is redirected to success/error page

**Listen to payment events to update your order/booking status:**

```php
// app/Providers/EventServiceProvider.php
use Greelogix\KPay\Events\PaymentStatusUpdated;

protected $listen = [
    PaymentStatusUpdated::class => [
        \App\Listeners\UpdateOrderStatus::class,
    ],
];
```

```php
// app/Listeners/UpdateOrderStatus.php
namespace App\Listeners;

use Greelogix\KPay\Events\PaymentStatusUpdated;
use App\Models\Order;

class UpdateOrderStatus
{
    public function handle(PaymentStatusUpdated $event)
    {
        $payment = $event->payment;
        $orderId = $payment->udf1; // Your order ID stored in udf1
        
        if ($payment->isSuccessful()) {
            $order = Order::find($orderId);
            if ($order) {
                $order->update(['status' => 'paid']);
                // Send confirmation email, etc.
            }
        } elseif ($payment->isFailed()) {
            $order = Order::find($orderId);
            if ($order) {
                $order->update(['status' => 'payment_failed']);
            }
        }
    }
}
```

### Get Payment Status

```php
use Greelogix\KPay\Facades\KPay;

// Get payment by track ID (your order ID)
$payment = KPay::getPaymentByTrackId('ORDER-12345');

if ($payment && $payment->isSuccessful()) {
    // Payment successful
}
```

### Inquiry Transaction Status

According to KNET documentation, use the inquiry feature to check the status of incomplete orders. This is recommended for verifying transaction status when response notifications may not have been received.

```php
use Greelogix\KPay\Facades\KPay;

// Inquiry transaction status from KNET
try {
    $response = KPay::inquiryTransaction('ORDER-12345');
    
    // Response contains:
    // - paymentid, result, auth, ref, tranid, postdate, etc.
    // - Payment record is automatically updated if found
    
    if ($response['result'] === 'CAPTURED') {
        // Transaction successful
    }
} catch (\Greelogix\KPay\Exceptions\KPayException $e) {
    // Handle error
    logger()->error('Inquiry failed: ' . $e->getMessage());
}
```

**Note:** The inquiry method automatically updates the payment record in your database if found, and validates the response hash for security.
}

// Get payment by transaction ID
$payment = KPay::getPaymentByTransId('TRANS123456');

// Check payment status
if ($payment->isSuccessful()) {
    // Success
} elseif ($payment->isFailed()) {
    // Failed
} elseif ($payment->isPending()) {
    // Pending
}
```

### Process Refund

```php
use Greelogix\KPay\Facades\KPay;

try {
    $refundResult = KPay::processRefund([
        'trans_id' => 'ORIGINAL_TRANSACTION_ID',
        'track_id' => 'REFUND-TRACK-ID',
        'amount' => 50.000,
    ]);

    if (isset($refundResult['result']) && $refundResult['result'] === 'CAPTURED') {
        // Refund successful
    }
} catch (\Greelogix\KPay\Exceptions\KPayException $e) {
    // Handle error
}
```

## Payment Response Handling

### Response Routes

The package automatically registers response routes:
- `POST /kpay/response` (route name: `kpay.response`)
- `GET /kpay/response` (route name: `kpay.response.get`)

These routes are **CSRF exempt** and handle payment responses from KNET.

### Success and Error Pages

The package includes built-in success and error pages:
- `GET /payment/success` (route name: `kpay.success`)
- `GET /payment/error` (route name: `kpay.error`)

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
\Greelogix\KPay\Events\PaymentStatusUpdated
```

## Configuration

All configuration is done via `.env` file or `config/kpay.php`:

```php
// config/kpay.php
return [
    'tranportal_id' => env('KPAY_TRANPORTAL_ID', ''),
    'tranportal_password' => env('KPAY_TRANPORTAL_PASSWORD', ''),
    'resource_key' => env('KPAY_RESOURCE_KEY', ''),
    'base_url' => env('KPAY_BASE_URL', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'),
    'test_mode' => env('KPAY_TEST_MODE', true),
    'response_url' => env('KPAY_RESPONSE_URL', ''),
    'error_url' => env('KPAY_ERROR_URL', ''),
    'currency' => env('KPAY_CURRENCY', '414'),
    'language' => env('KPAY_LANGUAGE', 'EN'),
    'kfast_enabled' => env('KPAY_KFAST_ENABLED', false),
    'apple_pay_enabled' => env('KPAY_APPLE_PAY_ENABLED', false),
    'payment_table' => env('KPAY_PAYMENT_TABLE', 'kpay_payments'),
    'create_payment_table' => env('KPAY_CREATE_TABLE', true),
];
```

### Using Existing Payment/Transactions Table

If you want to use your existing `payments` or `transactions` table:

1. **Set in `.env`:**
   ```env
   KPAY_PAYMENT_TABLE=payments
   # or
   KPAY_PAYMENT_TABLE=transactions
   
   KPAY_CREATE_TABLE=false
   ```

2. **Ensure your table has these columns:**
   - `id` (primary key)
   - `payment_id` (string, nullable)
   - `track_id` (string, nullable, indexed)
   - `result` (string, nullable)
   - `result_code` (string, nullable)
   - `auth` (string, nullable)
   - `ref` (string, nullable)
   - `trans_id` (string, nullable)
   - `post_date` (string, nullable)
   - `udf1`, `udf2`, `udf3`, `udf4`, `udf5` (string, nullable)
   - `amount` (decimal 15,3, nullable)
   - `currency` (string, nullable)
   - `payment_method` (string, nullable)
   - `status` (string, default: 'pending', indexed)
   - `response_data` (text, nullable)
   - `request_data` (text, nullable)
   - `created_at`, `updated_at` (timestamps)

3. **Skip migration:**
   ```bash
   # Don't run: php artisan migrate
   # Or set KPAY_CREATE_TABLE=false
   ```

**Note:** If your existing table has different column names, you may need to extend the `KPayPayment` model and override the `$fillable` array to match your schema.

## Complete Example

### 1. Create Order in Your System

```php
// In your checkout controller
$order = Order::create([
    'user_id' => auth()->id(),
    'total' => 100.000,
    'status' => 'pending',
    // ... other fields
]);
```

### 2. Initiate Payment

```php
use Greelogix\KPay\Facades\KPay;

$paymentData = KPay::generatePaymentForm([
    'amount' => $order->total,
    'track_id' => (string)$order->id,  // Use order ID as track_id
    'udf1' => (string)$order->id,      // Store order ID for event listener
    'currency' => '414',
    'language' => 'EN',
]);

return view('kpay::payment.form', [
    'formUrl' => $paymentData['form_url'],
    'formData' => $paymentData['form_data'],
]);
```

### 3. Handle Payment Event

```php
// app/Listeners/UpdateOrderStatus.php
public function handle(PaymentStatusUpdated $event)
{
    $payment = $event->payment;
    $orderId = $payment->udf1; // Your order ID
    
    if ($payment->isSuccessful()) {
        $order = Order::find($orderId);
        if ($order) {
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            // Send email, update inventory, etc.
        }
    }
}
```

**That's it!** The package handles everything else automatically.

## Testing

### Test Mode

**Important:** KNET test environment does NOT require any credentials for testing.

1. Set `KPAY_TEST_MODE=true` in `.env`
2. **Leave credentials empty** (Tranportal ID, Password, and Resource Key can be empty for testing)
3. Use test base URL: `https://kpaytest.com.kw/kpg/PaymentHTTP.htm`
4. You can test the payment flow without any credentials

### Test Cards

Refer to your acquiring bank for test card numbers. Test cards typically have:
- Expiration date: Future date (e.g., 12/2025)
- CVV: Any 3 digits

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

## Production Checklist

- [ ] Set `KPAY_TEST_MODE=false` in `.env`
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

## Security

- Response validation uses SHA-256 hash verification
- CSRF protection enabled (response routes are exempt)
- Resource key never exposed in frontend
- All payment data validated before processing

## Troubleshooting

### Routes Not Working

1. Clear route cache: `php artisan route:clear`
2. Verify package is discovered: `php artisan package:discover`
3. Check routes: `php artisan route:list | grep kpay`

### Payment Response Not Received

1. Check response URL is correctly configured in `.env`
2. Verify response URL is accessible from internet (not localhost)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Ensure CSRF exemption is working for response route

### Hash Validation Failed

1. Verify resource key is correct in `.env`
2. Check that all parameters are included in hash calculation
3. Ensure no parameters are modified before validation

## License

MIT

## Support

For issues and questions:

- Check the [KNET Integration Manual](https://www.knet.com.kw/)
- Review package documentation
- Contact: asad.ali@greelogix.com

## Changelog

### Version 2.0.0

- Simplified package - removed admin panels and settings management
- Payment methods now returned from service (standard methods)
- Configuration via config/env only
- Core payment functionality only

### Version 1.0.0

- Initial release
- Complete KNET Payment Gateway integration
- Payment response handling with validation
- Refund processing support
- KFAST support
- Apple Pay support
- Payment status tracking
