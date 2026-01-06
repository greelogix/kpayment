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
        
        if (empty($resourceKey) && $testMode) {
            $resourceKey = 'TEST_KEY_16_BYTE';
        }
        $this->resourceKey = $resourceKey;
        $this->testMode = $testMode;
        
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

    public function getPaymentMethods(string $platform = 'web'): array
    {
        return $this->getStandardPaymentMethods($platform);
    }

    protected function getStandardPaymentMethods(string $platform = 'web'): array
    {
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

        if ($this->kfastEnabled) {
            $methods[] = [
                'code' => 'KFAST',
                'name' => 'KFAST',
                'platform' => ['web', 'ios', 'android'],
            ];
        }

        if ($this->applePayEnabled) {
            $methods[] = [
                'code' => 'APPLE_PAY',
                'name' => 'Apple Pay',
                'platform' => ['ios', 'web'],
            ];
        }

        return array_values(array_filter($methods, function ($method) use ($platform) {
            return in_array(strtolower($platform), array_map('strtolower', $method['platform']));
        }));
    }

    public function generatePaymentForm(array $data): array
    {
        Log::debug('KPay: generatePaymentForm called', [
            'test_mode' => $this->testMode,
            'test_mode_type' => gettype($this->testMode),
            'has_tranportal_id' => !empty($this->tranportalId),
            'tranportal_id_length' => strlen($this->tranportalId ?? ''),
        ]);
        
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

        $responseUrl = $data['response_url'] ?? $this->responseUrl;
        $errorUrl = $data['error_url'] ?? $this->errorUrl;

        if (empty($responseUrl)) {
            throw new KPayException('responseURL is required for KNET payment. Please set APP_URL in .env (auto-generated) or configure KPAY_RESPONSE_URL');
        }

        if (empty($errorUrl)) {
            throw new KPayException('errorURL is required for KNET payment. Please set APP_URL in .env (auto-generated) or configure KPAY_ERROR_URL');
        }

        if (!filter_var($responseUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('responseURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        if (!filter_var($errorUrl, FILTER_VALIDATE_URL)) {
            throw new KPayException('errorURL must be a valid absolute URL (e.g., https://yoursite.com/kpay/response)');
        }

        if (strpos($responseUrl, 'http://') === 0 && strpos($responseUrl, 'localhost') === false && strpos($responseUrl, '127.0.0.1') === false) {
            Log::warning('KPay: responseURL uses HTTP instead of HTTPS - KNET may reject this', [
                'response_url' => $responseUrl,
            ]);
        }

        if (strpos($responseUrl, '/kpay/response') === false) {
            Log::warning('KPay: responseURL does not contain /kpay/response path', [
                'response_url' => $responseUrl,
                'note' => 'Ensure the route /kpay/response exists and is accessible',
            ]);
        }

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

        if (strpos($responseUrl, 'ngrok') !== false || strpos($responseUrl, 'trycloudflare') !== false) {
            Log::warning('KPay: Using tunneling service URL - ensure it allows automated server requests', [
                'response_url' => $responseUrl,
                'error_url' => $errorUrl,
                'note' => 'Some tunneling services may block automated requests. Test with: curl -X POST ' . $responseUrl,
            ]);
        }

        $tranportalId = $this->tranportalId ?? '';
        $tranportalPassword = $this->tranportalPassword ?? '';
        $resourceKey = $this->resourceKey ?? '';
        
        if ($this->testMode) {
            $placeholderValues = ['your_production_id', 'your_production_password', 'your_production_resource_key', 'YOUR_TRANPORTAL_ID', 'YOUR_TRANPORTAL_PASSWORD', 'YOUR_RESOURCE_KEY'];
            if (in_array($tranportalId, $placeholderValues, true)) {
                $tranportalId = '';
            }
            if (in_array($tranportalPassword, $placeholderValues, true)) {
                $tranportalPassword = '';
            }
            
            if (!empty($tranportalId)) {
                if (empty($resourceKey) || $resourceKey === 'TEST_KEY_16_BYTE') {
                    Log::warning('KPay: tranportalId provided but using default test resource key', [
                        'tranportal_id' => substr($tranportalId, 0, 4) . '***',
                        'resource_key' => 'DEFAULT_TEST_KEY',
                        'note' => 'If you provided a real tranportal ID, you MUST also provide the matching resource key from your bank. Using default test key with a real tranportal ID will cause KPAY to reject the request.',
                    ]);
                }
                
                if (empty($tranportalPassword)) {
                    Log::warning('KPay: tranportalId provided but password is empty', [
                        'tranportal_id' => substr($tranportalId, 0, 4) . '***',
                        'note' => 'Some KPAY accounts may require a password even in test mode. Contact your bank for test credentials.',
                    ]);
                }
            } else {
                $tranportalId = '';
            }
        }

        $language = strtoupper($data['language'] ?? $this->language);
        if ($language === 'EN' || $language === 'ENGLISH') {
            $language = 'USA';
        }
        
        $paramString = 'id=' . $tranportalId;
        $paramString .= '&password=' . $tranportalPassword;
        $paramString .= '&action=' . ($data['action'] ?? '1');
        $paramString .= '&langid=' . $language;
        $paramString .= '&currencycode=' . ($data['currency'] ?? $this->currency);
        $paramString .= '&amt=' . $amount;
        $paramString .= '&responseURL=' . $responseUrl;
        $paramString .= '&errorURL=' . $errorUrl;
        $paramString .= '&trackid=' . $trackId;

        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            if (isset($data[$key])) {
                $paramString .= '&' . $key . '=' . $data[$key];
            } else {
                $paramString .= '&' . $key . '=';
            }
        }

        $encryptedData = $this->encryptAES($paramString, $resourceKey);

        $trandata = $encryptedData . '&tranportalId=' . $tranportalId . '&responseURL=' . $responseUrl . '&errorURL=' . $errorUrl;

        $finalUrl = $this->baseUrl . '?param=paymentInit&trandata=' . $trandata;

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

        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            $params[$key] = $data[$key] ?? '';
        }

        $logParams = $params;
        if (isset($logParams['password'])) {
            $logParams['password'] = '***';
        }
        $urlParts = parse_url($finalUrl);
        $queryParams = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }
        
        $resourceKeyPreview = !empty($resourceKey) 
            ? (strlen($resourceKey) > 8 ? substr($resourceKey, 0, 4) . '***' . substr($resourceKey, -4) : '***')
            : 'EMPTY';
        $isDefaultTestKey = ($resourceKey === 'TEST_KEY_16_BYTE');
        
        Log::info('KPay: Payment form generated', [
            'track_id' => $trackId,
            'amount' => $amount,
            'response_url' => $responseUrl,
            'error_url' => $errorUrl,
            'test_mode' => $this->testMode,
            'final_url_length' => strlen($finalUrl),
            'trandata_preview' => substr($trandata, 0, 100) . '...',
            'has_error_url' => !empty($errorUrl),
            'has_response_url' => !empty($responseUrl),
            'error_url_in_trandata' => strpos($trandata, 'errorURL=') !== false,
            'tranportal_id' => $tranportalId ? 'SET' : 'EMPTY',
            'tranportal_id_length' => strlen($tranportalId),
            'tranportal_id_preview' => !empty($tranportalId) ? substr($tranportalId, 0, 4) . '***' : 'EMPTY',
            'resource_key_preview' => $resourceKeyPreview,
            'is_default_test_key' => $isDefaultTestKey,
            'resource_key_length' => strlen($resourceKey),
            'param_string_preview' => substr($paramString, 0, 150) . '...',
            'has_id_in_param' => strpos($paramString, 'id=') !== false,
            'has_tranportal_id_in_trandata' => strpos($trandata, 'tranportalId=') !== false,
            'url_has_tranportal_id_param' => isset($queryParams['tranportalId']),
            'url_tranportal_id_value' => $queryParams['tranportalId'] ?? 'NOT_FOUND',
            'url_has_response_url_param' => isset($queryParams['responseURL']),
            'url_has_error_url_param' => isset($queryParams['errorURL']),
        ]);

        try {
            DB::beginTransaction();
            
            $existingPayment = KPayPayment::where('track_id', $trackId)->first();
            if ($existingPayment && $existingPayment->status === 'pending') {
                Log::warning('KPay: Duplicate track_id detected, reusing existing payment', [
                    'track_id' => $trackId,
                    'existing_payment_id' => $existingPayment->id,
                ]);
                DB::rollBack();
                $payment = $existingPayment;
                $payment->update(['request_data' => $params]);
            } else {
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

    public function generatePaymentUrl(array $data): array
    {
        $paymentData = $this->generatePaymentForm($data);
        
        $formData = $paymentData['form_data'];
        unset($formData['payment_id']);
        
        return [
            'payment_url' => $paymentData['form_url'],
            'payment_id' => $paymentData['payment_id'],
            'track_id' => $paymentData['track_id'],
            'form_data' => $formData,
            'method' => 'POST',
        ];
    }

    public function generatePaymentRedirectUrl(array $data, ?string $redirectRoute = null): array
    {
        try {
            $paymentData = $this->generatePaymentForm($data);
            
            $formData = $paymentData['form_data'];
            unset($formData['payment_id']);
            
            try {
                if (function_exists('route')) {
                    $redirectUrl = route($redirectRoute ?? 'kpay.redirect', ['paymentId' => $paymentData['payment_id']]);
                } else {
                    throw new \Exception('route() helper not available');
                }
            } catch (\Exception $e) {
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

    public function validateResponse(array $response): bool
    {
        $response = $this->normalizeResponseKeys($response);
        
        if (!isset($response['hash'])) {
            Log::warning('KNET Response missing hash', ['response' => $response]);
            return false;
        }

        $receivedHash = strtoupper($response['hash']);
        
        $paramsForHash = $response;
        unset($paramsForHash['hash']);

        $hashString = $this->generateHashString($paramsForHash);
        $calculatedHash = $this->generateHash($hashString);

        return hash_equals($calculatedHash, $receivedHash);
    }

    public function processResponse(array $response): KPayPayment
    {
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
    
    protected function normalizeResponseKeys(array $response): array
    {
        $normalized = [];
        foreach ($response as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }
        return $normalized;
    }

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
            'action' => '2',
            'transid' => $data['trans_id'],
            'trackid' => $data['track_id'] ?? $this->generateTrackId(),
            'amt' => number_format((float)$data['amount'], 3, '.', ''),
        ];

        for ($i = 1; $i <= 5; $i++) {
            $key = 'udf' . $i;
            if (isset($data[$key])) {
                $params[$key] = $data[$key];
            }
        }

        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

        $response = $this->makeRefundRequest($params);
        
        $response = $this->normalizeResponseKeys($response);
        
        if (!$this->validateResponse($response)) {
            throw new KPayException('Refund response hash validation failed');
        }

        return $response;
    }

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

    protected function pkcs5_pad(string $text): string
    {
        $blocksize = 16;
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    protected function byteArray2Hex(array $byteArray): string
    {
        $chars = array_map("chr", $byteArray);
        $bin = join($chars);
        return bin2hex($bin);
    }

    protected function generateHashString(array $params): string
    {
        $hashString = $this->resourceKey ?? '';
        
        unset($params['hash']);
        
        ksort($params, SORT_STRING);
        
        foreach ($params as $key => $value) {
            $stringValue = trim((string)$value);
            if ($stringValue !== '') {
                $hashString .= $stringValue;
            }
        }

        return $hashString;
    }

    protected function generateHash(string $hashString): string
    {
        return strtoupper(hash('sha256', $hashString));
    }

    protected function generateTrackId(): string
    {
        return time() . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function determineStatus(array $response): string
    {
        $result = strtoupper(trim($response['result'] ?? ''));
        
        if (in_array($result, ['CAPTURED', 'SUCCESS'])) {
            return 'success';
        }
        
        if (in_array($result, ['NOT CAPTURED', 'NOTCAPTURED', 'FAILED', 'CANCELLED', 'CANCELED'])) {
            return 'failed';
        }
        
        if (isset($response['error']) && !empty($response['error'])) {
            return 'failed';
        }
        
        return 'pending';
    }

    protected function makeRefundRequest(array $params): array
    {
        try {
            Log::info('KNET Refund Request', [
                'url' => $this->baseUrl,
                'params' => array_merge($params, ['password' => '***', 'hash' => '***']),
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

    protected function parseResponse(string $response): array
    {
        if (strpos($response, '<?xml') !== false) {
            $xml = simplexml_load_string($response);
            return json_decode(json_encode($xml), true);
        }

        parse_str($response, $parsed);
        return $parsed;
    }

    public function getPaymentByTrackId(string $trackId): ?KPayPayment
    {
        return KPayPayment::where('track_id', $trackId)->first();
    }

    public function getPaymentByTransId(string $transId): ?KPayPayment
    {
        return KPayPayment::where('trans_id', $transId)->first();
    }

    public function getPaymentFormData(KPayPayment $payment): array
    {
        $formData = $payment->request_data;
        
        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?? [];
        }
        
        if (empty($formData) && is_array($payment->request_data)) {
            $formData = $payment->request_data;
        }
        
        if (empty($formData) || !is_array($formData)) {
            throw new KPayException('Payment request data not found');
        }
        
        if (isset($formData['final_url'])) {
            return $formData;
        }
        
        return $formData;
    }

    public function getBaseUrl(): string
    {
        if (!empty($this->baseUrl)) {
            return $this->baseUrl;
        }
        
        return $this->testMode 
            ? 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'
            : 'https://www.kpay.com.kw/kpg/PaymentHTTP.htm';
    }

    public function inquiryTransaction(string $trackId): array
    {
        $params = [
            'id' => $this->tranportalId,
            'password' => $this->tranportalPassword,
            'action' => '8',
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

            $responseData = $this->parseResponse($response->body());
            
            $responseData = $this->normalizeResponseKeys($responseData);
            
            if (!$this->validateResponse($responseData)) {
                throw new KPayException('Inquiry response hash validation failed');
            }
            
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
