<?php

namespace Greelogix\KPay\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Greelogix\KPay\Exceptions\KPayException;
use Greelogix\KPay\Models\KPayPayment;

class KPayService
{
    protected string $tranportalId;
    protected string $tranportalPassword;
    protected string $resourceKey;
    protected string $baseUrl;
    protected bool $testMode;
    protected string $responseUrl;
    protected string $errorUrl;
    protected string $currency;
    protected string $language;
    protected bool $kfastEnabled;
    protected bool $applePayEnabled;

    public function __construct(
        string $tranportalId,
        string $tranportalPassword,
        string $resourceKey,
        string $baseUrl,
        bool $testMode = true,
        string $responseUrl = '',
        string $errorUrl = '',
        string $currency = '414',
        string $language = 'USA',
        bool $kfastEnabled = false,
        bool $applePayEnabled = false
    ) {
        $this->tranportalId = $tranportalId;
        $this->tranportalPassword = $tranportalPassword;
        
        // Ensure resource key is set (use default test key if empty in test mode)
        if (empty($resourceKey) && $testMode) {
            $resourceKey = 'TEST_KEY_16_BYTE'; // Default 16-byte test key
        }
        $this->resourceKey = $resourceKey;
        $this->testMode = $testMode;
        
        // Auto-set base URL based on test mode if not provided
        if (empty($baseUrl)) {
            $this->baseUrl = $testMode 
                ? 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'
                : 'https://www.kpay.com.kw/kpg/PaymentHTTP.htm';
        } else {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
        
        $this->responseUrl = $responseUrl;
        $this->errorUrl = $errorUrl;
        $this->currency = $currency;
        $this->language = $language;
        $this->kfastEnabled = $kfastEnabled;
        $this->applePayEnabled = $applePayEnabled;
    }

    /**
     * Get available payment methods
     * Returns standard KNET payment methods
     */
    public function getPaymentMethods(string $platform = 'web'): array
    {
        return $this->getStandardPaymentMethods($platform);
    }

    /**
     * Get standard payment methods (fallback)
     */
    protected function getStandardPaymentMethods(string $platform = 'web'): array
    {
        // Standard KNET payment methods
        $methods = [
            [
                'code' => 'KNET',
                'name' => 'KNET Card',
                'platform' => ['web', 'ios', 'android'],
            ],
            [
                'code' => 'VISA',
                'name' => 'Visa',
                'platform' => ['web', 'ios', 'android'],
            ],
            [
                'code' => 'MASTERCARD',
                'name' => 'Mastercard',
                'platform' => ['web', 'ios', 'android'],
            ],
        ];

        // Add KFAST if enabled
        if ($this->kfastEnabled) {
            $methods[] = [
                'code' => 'KFAST',
                'name' => 'KFAST',
                'platform' => ['web', 'ios', 'android'],
            ];
        }

        // Add Apple Pay if enabled
        if ($this->applePayEnabled) {
            $methods[] = [
                'code' => 'APPLE_PAY',
                'name' => 'Apple Pay',
                'platform' => ['ios', 'web'],
            ];
        }

        // Filter by platform
        return array_values(array_filter($methods, function ($method) use ($platform) {
            return in_array(strtolower($platform), array_map('strtolower', $method['platform']));
        }));
    }


    /**
     * Generate payment form data
     * According to KNET documentation, responseURL and errorURL are REQUIRED
     */
    public function generatePaymentForm(array $data): array
    {
        // Validate production credentials
        if (!$this->testMode) {
            if (empty($this->tranportalId)) {
                throw new KPayException('KPAY_TRANPORTAL_ID is required for production mode. Please configure it in .env');
            }
            if (empty($this->tranportalPassword)) {
                throw new KPayException('KPAY_TRANPORTAL_PASSWORD is required for production mode. Please configure it in .env');
            }
            if (empty($this->resourceKey)) {
                throw new KPayException('KPAY_RESOURCE_KEY is required for production mode. Please configure it in .env');
            }
        }

        $trackId = $data['track_id'] ?? $this->generateTrackId();
        $amount = number_format((float)($data['amount'] ?? 0), 3, '.', '');

        // Get response and error URLs (REQUIRED by KNET)
        $responseUrl = $data['response_url'] ?? $this->responseUrl;
        $errorUrl = $data['error_url'] ?? $this->errorUrl;

        // Validate required URLs
        if (empty($responseUrl)) {
            throw new KPayException('responseURL is required for KNET payment. Please set APP_URL in .env (auto-generated) or configure KPAY_RESPONSE_URL');
        }

        if (empty($errorUrl)) {
            throw new KPayException('errorURL is required for KNET payment. Please set APP_URL in .env (auto-generated) or configure KPAY_ERROR_URL');
        }

        // Ensure URLs are absolute and valid
        if (!filter_var($responseUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('responseURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        if (!filter_var($errorUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('errorURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        // KNET requires HTTPS for response URLs (except for local testing)
        if (strpos($responseUrl, 'http://') === 0 && strpos($responseUrl, 'localhost') === false && strpos($responseUrl, '127.0.0.1') === false) {
            Log::warning('KPay: responseURL uses HTTP instead of HTTPS - KNET may reject this', [
                'response_url' => $responseUrl,
            ]);
        }

        // Validate URL format matches KNET requirements
        // KNET expects responseURL to be accessible and return a valid response
        if (strpos($responseUrl, '/kpay/response') === false) {
            Log::warning('KPay: responseURL does not contain /kpay/response path', [
                'response_url' => $responseUrl,
                'note' => 'Ensure the route /kpay/response exists and is accessible',
            ]);
        }

        // Reject localhost URLs - KNET servers cannot access them
        $localhostPatterns = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
        $isLocalhost = false;
        foreach ($localhostPatterns as $pattern) {
            if (strpos($responseUrl, $pattern) !== false || strpos($errorUrl, $pattern) !== false) {
                $isLocalhost = true;
                break;
            }
        }

        if ($isLocalhost) {
            throw new KPayException(
                'KNET servers cannot access localhost URLs. ' .
                'Please set APP_URL to a publicly accessible URL (e.g., https://yourdomain.com or ngrok URL). ' .
                'Current APP_URL: ' . ($responseUrl ?: 'not set') . '. ' .
                'For testing, use ngrok or a public domain. Update your .env file: APP_URL=https://your-public-url.com'
            );
        }

        // Warn if using tunneling services (may have limitations)
        if (strpos($responseUrl, 'ngrok') !== false || strpos($responseUrl, 'trycloudflare') !== false) {
            Log::warning('KPay: Using tunneling service URL - ensure it allows automated server requests', [
                'response_url' => $responseUrl,
                'error_url' => $errorUrl,
                'note' => 'Some tunneling services may block automated requests. Test with: curl -X POST ' . $responseUrl,
            ]);
        }

        // Get credentials
        $tranportalId = $this->tranportalId ?? '';
        $tranportalPassword = $this->tranportalPassword ?? '';
        
        // Remove placeholder values in test mode
        if ($this->testMode) {
            $placeholderValues = ['your_production_id', 'your_production_password', 'your_production_resource_key', 'YOUR_TRANPORTAL_ID', 'YOUR_TRANPORTAL_PASSWORD', 'YOUR_RESOURCE_KEY'];
            if (in_array($tranportalId, $placeholderValues, true)) {
                $tranportalId = '';
            }
            if (in_array($tranportalPassword, $placeholderValues, true)) {
                $tranportalPassword = '';
            }
        }

        // Normalize language code (KPAY requires USA or AR, not EN)
        $language = strtoupper($data['language'] ?? $this->language);
        if ($language === 'EN' || $language === 'ENGLISH') {
            $language = 'USA';
        }
        
        // Build parameter string in exact order as per KPAY reference code
        // Order: id&password&action&langid&currencycode&amt&responseURL&errorURL&trackid&udf1&udf2&udf3&udf4&udf5
        $paramString = 'id=' . $tranportalId;
        $paramString .= '&password=' . $tranportalPassword;
        $paramString .= '&action=' . ($data['action'] ?? '1');
        $paramString .= '&langid=' . $language;
        $paramString .= '&currencycode=' . ($data['currency'] ?? $this->currency);
        $paramString .= '&amt=' . $amount;
        $paramString .= '&responseURL=' . $responseUrl;
        $paramString .= '&errorURL=' . $errorUrl;
        $paramString .= '&trackid=' . $trackId;

        // Add UDF fields if provided
        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            if (isset($data[$key])) {
                $paramString .= '&' . $key . '=' . $data[$key];
            } else {
                // Add empty UDF if not provided (to maintain order)
                $paramString .= '&' . $key . '=';
            }
        }

        // Encrypt the parameter string using AES-128-CBC (as per KPAY reference code)
        $encryptedData = $this->encryptAES($paramString, $this->resourceKey ?? '');

        // Build trandata parameter as per SendPerformREQuest.php line 129
        // Format: ENCRYPTED_DATA&tranportalId=ID&responseURL=URL&errorURL=URL
        // Note: Only encode the encrypted part, keep & characters for URL parameters
        $trandata = $encryptedData . '&tranportalId=' . urlencode($tranportalId) . '&responseURL=' . urlencode($responseUrl) . '&errorURL=' . urlencode($errorUrl);

        // Build final URL as per SendPerformREQuest.php line 139
        // Format: baseUrl?param=paymentInit&trandata=ENCRYPTED&tranportalId=ID&responseURL=URL&errorURL=URL
        // Note: Only encode the encrypted part of trandata, not the entire string
        $finalUrl = $this->baseUrl . '?param=paymentInit&trandata=' . $trandata;

        // Store original parameters for logging and payment record
        $params = [
            'id' => $tranportalId,
            'password' => $tranportalPassword,
            'action' => $data['action'] ?? '1',
            'langid' => $language,
            'currencycode' => $data['currency'] ?? $this->currency,
            'amt' => $amount,
            'responseURL' => $responseUrl,
            'errorURL' => $errorUrl,
            'trackid' => $trackId,
        ];

        // Add UDF fields
        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            $params[$key] = $data[$key] ?? '';
        }

        // Log form parameters for debugging (without sensitive data)
        $logParams = $params;
        if (isset($logParams['password'])) {
            $logParams['password'] = '***';
        }
        Log::info('KPay: Payment form generated', [
            'track_id' => $trackId,
            'amount' => $amount,
            'response_url' => $responseUrl,
            'error_url' => $errorUrl,
            'test_mode' => $this->testMode,
            'final_url_length' => strlen($finalUrl),
        ]);

        // Create payment record within transaction for data integrity
        try {
            DB::beginTransaction();
            
            // Check for duplicate track_id (prevent race conditions)
            $existingPayment = KPayPayment::where('track_id', $trackId)->first();
            if ($existingPayment && $existingPayment->status === 'pending') {
                // If pending payment exists with same track_id, reuse it
                Log::warning('KPay: Duplicate track_id detected, reusing existing payment', [
                    'track_id' => $trackId,
                    'existing_payment_id' => $existingPayment->id,
                ]);
                DB::rollBack();
                $payment = $existingPayment;
                // Update request_data if needed
                $payment->update(['request_data' => $params]);
            } else {
                // Create new payment record
                $payment = KPayPayment::create([
                    'track_id' => $trackId,
                    'amount' => $amount,
                    'currency' => $params['currencycode'],
                    'payment_method' => $data['payment_method_code'] ?? null,
                    'status' => 'pending',
                    'request_data' => $params,
                ]);
            }
            
            DB::commit();
            
            Log::info('KPay: Payment record created', [
                'payment_id' => $payment->id,
                'track_id' => $trackId,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KPay: Failed to create payment record', [
                'track_id' => $trackId,
                'error' => $e->getMessage(),
            ]);
            throw new KPayException('Failed to create payment record: ' . $e->getMessage());
        }

        // Store the final URL in request_data for reference
        $params['final_url'] = $finalUrl;
        $params['encrypted_data'] = $encryptedData;
        $params['payment_id'] = $payment->id;

        return [
            'form_url' => $finalUrl,
            'redirect_url' => $finalUrl,
            'form_data' => $params,
            'payment_id' => $payment->id,
            'track_id' => $trackId,
        ];
    }

    /**
     * Generate payment URL for API usage
     * Returns payment URL and data in a format suitable for API responses
     * 
     * @param array $data Payment data (same as generatePaymentForm)
     * @return array Contains payment_url, payment_id, track_id, and form_data
     * @throws KPayException
     */
    public function generatePaymentUrl(array $data): array
    {
        // Use the existing generatePaymentForm method to get all the data
        $paymentData = $this->generatePaymentForm($data);
        
        // Remove payment_id from form_data (internal field, not for KNET)
        $formData = $paymentData['form_data'];
        unset($formData['payment_id']);
        
        // Return API-friendly structure
        return [
            'payment_url' => $paymentData['form_url'],
            'payment_id' => $paymentData['payment_id'],
            'track_id' => $paymentData['track_id'],
            'form_data' => $formData,
            'method' => 'POST', // KNET requires POST
        ];
    }

    /**
     * Generate payment redirect URL
     * Creates a URL in your app that will auto-submit the form to KNET
     * This is useful for mobile apps or when you need a direct redirect URL
     * 
     * @param array $data Payment data (same as generatePaymentForm)
     * @param string|null $redirectRoute Optional custom route name (default: kpay.redirect)
     * @return array Contains redirect_url, payment_id, track_id, and form_data
     * @throws KPayException
     */
    public function generatePaymentRedirectUrl(array $data, ?string $redirectRoute = null): array
    {
        try {
            // Generate payment form data (creates payment record)
            $paymentData = $this->generatePaymentForm($data);
            
            // Remove payment_id from form_data (internal field, not for KNET)
            $formData = $paymentData['form_data'];
            unset($formData['payment_id']);
            
            // Generate redirect URL using route helper if available, otherwise construct manually
            try {
                if (function_exists('route')) {
                    $redirectUrl = route($redirectRoute ?? 'kpay.redirect', ['paymentId' => $paymentData['payment_id']]);
                } else {
                    throw new \Exception('route() helper not available');
                }
            } catch (\Exception $e) {
                // Fallback to manual URL construction if route helper fails
                $appUrl = config('app.url', '');
                if (empty($appUrl)) {
                    throw new KPayException('APP_URL is required for redirect URL generation. Please set it in .env');
                }
                $redirectUrl = rtrim($appUrl, '/') . '/kpay/redirect/' . $paymentData['payment_id'];
            }
            
            Log::info('KPay: Payment redirect URL generated', [
                'payment_id' => $paymentData['payment_id'],
                'track_id' => $paymentData['track_id'],
                'redirect_url' => $redirectUrl,
            ]);
            
            return [
                'redirect_url' => $redirectUrl,
                'payment_url' => $paymentData['form_url'],
                'payment_id' => $paymentData['payment_id'],
                'track_id' => $paymentData['track_id'],
                'form_data' => $formData,
                'method' => 'POST',
            ];
        } catch (KPayException $e) {
            Log::error('KPay: Failed to generate redirect URL', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('KPay: Unexpected error generating redirect URL', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw new KPayException('Failed to generate payment redirect URL: ' . $e->getMessage());
        }
    }

    /**
     * Validate payment response
     * Validates the hash signature from KNET response
     */
    public function validateResponse(array $response): bool
    {
        // Normalize response keys
        $response = $this->normalizeResponseKeys($response);
        
        if (!isset($response['hash'])) {
            Log::warning('KNET Response missing hash', ['response' => $response]);
            return false;
        }

        $receivedHash = strtoupper($response['hash']);
        
        // Create copy for hash calculation (without hash field)
        $paramsForHash = $response;
        unset($paramsForHash['hash']);

        $hashString = $this->generateHashString($paramsForHash);
        $calculatedHash = $this->generateHash($hashString);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Process payment response
     * Handles KNET response with proper field mapping according to KNET documentation
     */
    public function processResponse(array $response): KPayPayment
    {
        // Normalize response keys (handle both lowercase and original case)
        $response = $this->normalizeResponseKeys($response);
        
        if (!$this->validateResponse($response)) {
            throw new KPayException(Lang::get('kpay.response.invalid_hash'));
        }

        $trackId = $response['trackid'] ?? null;
        if (!$trackId) {
            throw new KPayException(Lang::get('kpay.response.track_id_not_found'));
        }

        $payment = KPayPayment::where('track_id', $trackId)->first();
        if (!$payment) {
            throw new KPayException(Lang::get('kpay.response.payment_not_found'));
        }

        $status = $this->determineStatus($response);
        
        // Map KNET response fields to database columns
        // KNET uses lowercase field names: paymentid, tranid, trackid, postdate, etc.
        $payment->update([
            'payment_id' => $response['paymentid'] ?? $response['PaymentID'] ?? null,
            'result' => $response['result'] ?? $response['Result'] ?? null,
            'result_code' => $response['result'] ?? $response['Result'] ?? null,
            'auth' => $response['auth'] ?? $response['Auth'] ?? null,
            'ref' => $response['ref'] ?? $response['Ref'] ?? null,
            'trans_id' => $response['tranid'] ?? $response['TranID'] ?? null,
            'post_date' => $response['postdate'] ?? $response['PostDate'] ?? null,
            'udf1' => $response['udf1'] ?? $response['UDF1'] ?? null,
            'udf2' => $response['udf2'] ?? $response['UDF2'] ?? null,
            'udf3' => $response['udf3'] ?? $response['UDF3'] ?? null,
            'udf4' => $response['udf4'] ?? $response['UDF4'] ?? null,
            'udf5' => $response['udf5'] ?? $response['UDF5'] ?? null,
            'status' => $status,
            'response_data' => $response,
        ]);

        return $payment;
    }
    
    /**
     * Normalize response keys to lowercase for consistent processing
     * KNET may return keys in different cases
     */
    protected function normalizeResponseKeys(array $response): array
    {
        $normalized = [];
        foreach ($response as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }
        return $normalized;
    }

    /**
     * Process refund
     * According to KNET documentation: action = 2 for refund
     * Requires original transaction ID (tranid) and amount
     * 
     * @param array $data Must contain 'trans_id' (original transaction ID) and 'amount'
     * @return array Response from KNET refund API
     * @throws KPayException
     */
    public function processRefund(array $data): array
    {
        if (empty($data['trans_id'])) {
            throw new KPayException('Transaction ID is required for refund');
        }

        if (empty($data['amount']) || (float)$data['amount'] <= 0) {
            throw new KPayException('Valid refund amount is required');
        }

        $params = [
            'id' => $this->tranportalId,
            'password' => $this->tranportalPassword,
            'action' => '2', // 2 = Refund (according to KNET documentation)
            'transid' => $data['trans_id'], // Original transaction ID from KNET
            'trackid' => $data['track_id'] ?? $this->generateTrackId(), // New track ID for refund
            'amt' => number_format((float)$data['amount'], 3, '.', ''), // Amount with 3 decimal places
        ];

        // Add UDF fields if provided
        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            if (isset($data[$key])) {
                $params[$key] = $data[$key];
            }
        }

        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

        // Make refund request (KNET uses HTTP POST for refunds)
        $response = $this->makeRefundRequest($params);
        
        // Normalize response keys
        $response = $this->normalizeResponseKeys($response);
        
        // Validate response hash
        if (!$this->validateResponse($response)) {
            throw new KPayException('Refund response hash validation failed');
        }

        return $response;
    }

    /**
     * Encrypt data using AES-128-CBC (as per KPAY reference code)
     * Matches the encryption method in SendPerformRequest.php
     */
    protected function encryptAES(string $str, string $key): string
    {
        $str = $this->pkcs5_pad($str);
        $encrypted = openssl_encrypt($str, 'AES-128-CBC', $key, OPENSSL_ZERO_PADDING, $key);
        $encrypted = base64_decode($encrypted);
        $encrypted = unpack('C*', $encrypted);
        $encrypted = $this->byteArray2Hex($encrypted);
        $encrypted = urlencode($encrypted);
        return $encrypted;
    }

    /**
     * PKCS5 padding for AES encryption
     */
    protected function pkcs5_pad(string $text): string
    {
        $blocksize = 16;
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * Convert byte array to hex string
     */
    protected function byteArray2Hex(array $byteArray): string
    {
        $chars = array_map("chr", $byteArray);
        $bin = join($chars);
        return bin2hex($bin);
    }

    /**
     * Generate hash string for signature (used for response validation)
     * According to KNET documentation: resource_key + sorted parameter values (alphabetically by key)
     * Empty values and null values are excluded
     */
    protected function generateHashString(array $params): string
    {
        $hashString = $this->resourceKey ?? '';
        
        // Remove hash from params before sorting
        unset($params['hash']);
        
        // Sort parameters by key alphabetically
        ksort($params, SORT_STRING);
        
        // Concatenate parameter values in sorted order (only non-empty values)
        foreach ($params as $key => $value) {
            $stringValue = trim((string)$value);
            if ($stringValue !== '') {
                $hashString .= $stringValue;
            }
        }

        return $hashString;
    }

    /**
     * Generate hash (used for response validation)
     */
    protected function generateHash(string $hashString): string
    {
        return strtoupper(hash('sha256', $hashString));
    }

    /**
     * Generate unique track ID
     * KNET requires unique track ID for each transaction
     * Format: timestamp + random number (minimum 4 digits, maximum 40 characters total)
     */
    protected function generateTrackId(): string
    {
        // Generate unique track ID: timestamp + random 6-digit number
        // This ensures uniqueness and follows KNET requirements
        return time() . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Determine payment status from response
     * According to KNET documentation:
     * - CAPTURED = Successful transaction
     * - NOT CAPTURED = Failed transaction
     * - Other values = Pending or error
     */
    protected function determineStatus(array $response): string
    {
        $result = strtoupper(trim($response['result'] ?? ''));
        
        // Successful statuses
        if (in_array($result, ['CAPTURED', 'SUCCESS'])) {
            return 'success';
        }
        
        // Failed statuses
        if (in_array($result, ['NOT CAPTURED', 'NOTCAPTURED', 'FAILED', 'CANCELLED', 'CANCELED'])) {
            return 'failed';
        }
        
        // Check for error codes (if present)
        if (isset($response['error']) && !empty($response['error'])) {
            return 'failed';
        }
        
        // Default to pending for unknown statuses
        return 'pending';
    }

    /**
     * Make refund request to KNET
     * Handles HTTP POST request to KNET Payment Gateway
     */
    protected function makeRefundRequest(array $params): array
    {
        try {
            Log::info('KNET Refund Request', [
                'url' => $this->baseUrl,
                'params' => array_merge($params, ['password' => '***', 'hash' => '***']), // Hide sensitive data
            ]);

            $response = Http::timeout(30)
                ->asForm()
                ->post($this->baseUrl, $params);

            if (!$response->successful()) {
                Log::error('KNET Refund HTTP Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new KPayException('Refund request failed: HTTP ' . $response->status());
            }

            // Parse response (KNET returns form-encoded or XML)
            $responseData = $this->parseResponse($response->body());
            
            Log::info('KNET Refund Response', [
                'response' => $responseData,
            ]);

            return $responseData;
        } catch (KPayException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('KNET Refund Error', [
                'params' => array_merge($params, ['password' => '***', 'hash' => '***']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new KPayException('Refund request failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse KNET response
     */
    protected function parseResponse(string $response): array
    {
        // KNET may return XML or form-encoded data
        if (strpos($response, '<?xml') !== false) {
            $xml = simplexml_load_string($response);
            return json_decode(json_encode($xml), true);
        }

        // Parse form-encoded response
        parse_str($response, $parsed);
        return $parsed;
    }

    /**
     * Get payment by track ID
     */
    public function getPaymentByTrackId(string $trackId): ?KPayPayment
    {
        return KPayPayment::where('track_id', $trackId)->first();
    }

    /**
     * Get payment by transaction ID
     */
    public function getPaymentByTransId(string $transId): ?KPayPayment
    {
        return KPayPayment::where('trans_id', $transId)->first();
    }

    /**
     * Get payment form data from payment record
     * Returns the final URL for redirect (stored during payment creation)
     * 
     * @param KPayPayment $payment
     * @return array Contains final_url for redirect
     * @throws KPayException
     */
    public function getPaymentFormData(KPayPayment $payment): array
    {
        $formData = $payment->request_data;
        
        // Handle if request_data is stored as JSON string
        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?? [];
        }
        
        // If still empty, try to get from array cast
        if (empty($formData) && is_array($payment->request_data)) {
            $formData = $payment->request_data;
        }
        
        // Validate that form data exists
        if (empty($formData) || !is_array($formData)) {
            throw new KPayException('Payment request data not found');
        }
        
        // Return the stored final_url if available
        if (isset($formData['final_url'])) {
            return $formData;
        }
        
        // If final_url not stored, return the form data as-is
        // (for backward compatibility with old payment records)
        return $formData;
    }

    /**
     * Get base URL for KNET payment gateway
     * 
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (!empty($this->baseUrl)) {
            return $this->baseUrl;
        }
        
        return $this->testMode 
            ? 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'
            : 'https://www.kpay.com.kw/kpg/PaymentHTTP.htm';
    }

    /**
     * Inquiry transaction status from KNET
     * According to KNET documentation, this should be used to check incomplete orders
     * 
     * @param string $trackId The track ID of the transaction to inquire
     * @return array Response from KNET inquiry API
     * @throws KPayException
     */
    public function inquiryTransaction(string $trackId): array
    {
        $params = [
            'id' => $this->tranportalId,
            'password' => $this->tranportalPassword,
            'action' => '8', // 8 = Inquiry
            'trackid' => $trackId,
        ];

        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

        try {
            $response = Http::asForm()
                ->post($this->baseUrl, $params);

            if (!$response->successful()) {
                throw new KPayException('Inquiry request failed: ' . $response->body());
            }

            // Parse response
            $responseData = $this->parseResponse($response->body());
            
            // Normalize response keys
            $responseData = $this->normalizeResponseKeys($responseData);
            
            // Validate response hash
            if (!$this->validateResponse($responseData)) {
                throw new KPayException('Inquiry response hash validation failed');
            }
            
            // Update payment record if found
            $payment = KPayPayment::where('track_id', $trackId)->first();
            if ($payment) {
                $status = $this->determineStatus($responseData);
                $payment->update([
                    'payment_id' => $responseData['paymentid'] ?? null,
                    'result' => $responseData['result'] ?? null,
                    'result_code' => $responseData['result'] ?? null,
                    'auth' => $responseData['auth'] ?? null,
                    'ref' => $responseData['ref'] ?? null,
                    'trans_id' => $responseData['tranid'] ?? null,
                    'post_date' => $responseData['postdate'] ?? null,
                    'status' => $status,
                    'response_data' => $responseData,
                ]);
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('KNET Inquiry Error', [
                'track_id' => $trackId,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw new KPayException('Inquiry request failed: ' . $e->getMessage());
        }
    }
}

