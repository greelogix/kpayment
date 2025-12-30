<?php

namespace Greelogix\KPay\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
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
        string $language = 'EN',
        bool $kfastEnabled = false,
        bool $applePayEnabled = false
    ) {
        $this->tranportalId = $tranportalId;
        $this->tranportalPassword = $tranportalPassword;
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

        // Ensure URLs are absolute
        if (!filter_var($responseUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('responseURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        if (!filter_var($errorUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('errorURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        // Build parameters according to KNET specification
        $params = [
            'action' => $data['action'] ?? '1', // 1 = Purchase
            'langid' => $data['language'] ?? $this->language,
            'currencycode' => $data['currency'] ?? $this->currency,
            'amt' => $amount,
            'trackid' => $trackId,
            'responseURL' => $responseUrl,
            'errorURL' => $errorUrl,
        ];

        // Add credentials (required for production, optional for test)
        // Note: KNET requires 'id' and 'password' fields to be present in the form submission
        // Even in test mode, these fields should be included (can be empty strings)
        // However, empty values are excluded from hash calculation
        $params['id'] = $this->tranportalId ?? '';
        $params['password'] = $this->tranportalPassword ?? '';

        // Store selected payment method in UDF1 if provided
        if (isset($data['payment_method_code'])) {
            $params['udf1'] = $data['payment_method_code'];
        }

        // Add other UDF fields if provided
        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            if (isset($data[$key]) && !isset($params[$key])) {
                $params[$key] = $data[$key];
            }
        }

        // Generate hash (resource_key is required for hash generation)
        // In test mode, if resource_key is empty, we still need to generate hash with empty string
        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

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

        $params['payment_id'] = $payment->id;

        return [
            'form_url' => $this->baseUrl,
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
                $appUrl = Config::get('app.url', '');
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
     * Generate hash string for signature
     * According to KNET documentation: resource_key + sorted parameter values (alphabetically by key)
     * Empty values and null values are excluded
     */
    protected function generateHashString(array $params): string
    {
        // KNET hash format: resource_key + param1_value + param2_value + ... + paramN_value
        // Parameters must be sorted alphabetically by key name
        $hashString = $this->resourceKey;
        
        // Remove hash from params before sorting
        unset($params['hash']);
        
        // Sort parameters by key alphabetically (case-insensitive)
        ksort($params, SORT_STRING | SORT_FLAG_CASE);
        
        // Concatenate parameter values in sorted order
        foreach ($params as $key => $value) {
            // Only include non-empty values (KNET requirement)
            if ($value !== null && $value !== '') {
                $hashString .= (string)$value;
            }
        }

        return $hashString;
    }

    /**
     * Generate hash
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

            $response = \Illuminate\Support\Facades\Http::timeout(30)
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
     * Extracts and validates form data for redirect
     * 
     * @param KPayPayment $payment
     * @return array Form data ready for KNET submission
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
        
        // Remove payment_id from form data (internal field, not for KNET)
        unset($formData['payment_id']);
        
        // Ensure 'id' and 'password' fields are present (required by KNET, even if empty)
        // This handles cases where payment was created before these fields were always included
        if (!isset($formData['id'])) {
            $formData['id'] = $this->tranportalId ?? '';
        }
        if (!isset($formData['password'])) {
            $formData['password'] = $this->tranportalPassword ?? '';
        }
        
        // Validate required KNET parameters
        $requiredParams = ['action', 'amt', 'trackid', 'responseURL', 'errorURL', 'hash'];
        $missingParams = array_diff($requiredParams, array_keys($formData));
        
        if (!empty($missingParams)) {
            throw new KPayException('Payment request is incomplete. Missing: ' . implode(', ', $missingParams));
        }
        
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
            $response = \Illuminate\Support\Facades\Http::asForm()
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

