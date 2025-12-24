<?php

namespace Greelogix\KPayment\Services;

use Illuminate\Support\Facades\Log;
use Greelogix\KPayment\Exceptions\KnetException;
use Greelogix\KPayment\Models\KnetPayment;
use Greelogix\KPayment\Models\PaymentMethod;
use Greelogix\KPayment\Models\SiteSetting;

class KnetService
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
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->testMode = $testMode;
        $this->responseUrl = $responseUrl;
        $this->errorUrl = $errorUrl;
        $this->currency = $currency;
        $this->language = $language;
        $this->kfastEnabled = $kfastEnabled;
        $this->applePayEnabled = $applePayEnabled;
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(string $platform = 'web'): array
    {
        $query = PaymentMethod::active();
        
        // Filter by platform
        $column = match(strtolower($platform)) {
            'ios' => 'is_ios_enabled',
            'android' => 'is_android_enabled',
            'web' => 'is_web_enabled',
            default => 'is_web_enabled',
        };
        
        return $query->where($column, true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Generate payment form data
     */
    public function generatePaymentForm(array $data): array
    {
        $trackId = $data['track_id'] ?? $this->generateTrackId();
        $amount = number_format((float)($data['amount'] ?? 0), 3, '.', '');

        $params = [
            'id' => $this->tranportalId,
            'password' => $this->tranportalPassword,
            'action' => $data['action'] ?? '1', // 1 = Purchase
            'langid' => $data['language'] ?? $this->language,
            'currencycode' => $data['currency'] ?? $this->currency,
            'amt' => $amount,
            'trackid' => $trackId,
            'responseURL' => $data['response_url'] ?? $this->responseUrl,
            'errorURL' => $data['error_url'] ?? $this->errorUrl,
        ];

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

        // Generate hash
        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

        // Create payment record
        $payment = KnetPayment::create([
            'track_id' => $trackId,
            'amount' => $amount,
            'currency' => $params['currencycode'],
            'payment_method' => $data['payment_method_code'] ?? null,
            'status' => 'pending',
            'request_data' => $params,
        ]);

        $params['payment_id'] = $payment->id;

        return [
            'form_url' => $this->baseUrl,
            'form_data' => $params,
            'payment_id' => $payment->id,
            'track_id' => $trackId,
        ];
    }

    /**
     * Validate payment response
     */
    public function validateResponse(array $response): bool
    {
        if (!isset($response['hash'])) {
            return false;
        }

        $receivedHash = $response['hash'];
        unset($response['hash']);

        $hashString = $this->generateHashString($response);
        $calculatedHash = $this->generateHash($hashString);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Process payment response
     */
    public function processResponse(array $response): KnetPayment
    {
        if (!$this->validateResponse($response)) {
            throw new KnetException('Invalid payment response hash');
        }

        $trackId = $response['trackid'] ?? null;
        if (!$trackId) {
            throw new KnetException('Track ID not found in response');
        }

        $payment = KnetPayment::where('track_id', $trackId)->first();
        if (!$payment) {
            throw new KnetException('Payment not found');
        }

        $status = $this->determineStatus($response);
        
        $payment->update([
            'payment_id' => $response['paymentid'] ?? null,
            'result' => $response['result'] ?? null,
            'result_code' => $response['result'] ?? null,
            'auth' => $response['auth'] ?? null,
            'ref' => $response['ref'] ?? null,
            'trans_id' => $response['tranid'] ?? null,
            'post_date' => $response['postdate'] ?? null,
            'udf1' => $response['udf1'] ?? null,
            'udf2' => $response['udf2'] ?? null,
            'udf3' => $response['udf3'] ?? null,
            'udf4' => $response['udf4'] ?? null,
            'udf5' => $response['udf5'] ?? null,
            'status' => $status,
            'response_data' => $response,
        ]);

        return $payment;
    }

    /**
     * Process refund
     */
    public function processRefund(array $data): array
    {
        $params = [
            'id' => $this->tranportalId,
            'password' => $this->tranportalPassword,
            'action' => '2', // 2 = Refund
            'transid' => $data['trans_id'], // Original transaction ID
            'trackid' => $data['track_id'] ?? $this->generateTrackId(),
            'amt' => number_format((float)($data['amount'] ?? 0), 3, '.', ''),
        ];

        $hashString = $this->generateHashString($params);
        $params['hash'] = $this->generateHash($hashString);

        // Make refund request (KNET uses HTTP POST for refunds)
        $response = $this->makeRefundRequest($params);

        return $response;
    }

    /**
     * Generate hash string for signature
     */
    protected function generateHashString(array $params): string
    {
        // KNET hash format: resource_key + param1 + param2 + ... + paramN
        $hashString = $this->resourceKey;
        
        // Sort parameters by key
        ksort($params);
        
        foreach ($params as $key => $value) {
            if ($key !== 'hash' && $value !== null && $value !== '') {
                $hashString .= $value;
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
     */
    protected function generateTrackId(): string
    {
        return time() . rand(1000, 9999);
    }

    /**
     * Determine payment status from response
     */
    protected function determineStatus(array $response): string
    {
        $result = $response['result'] ?? '';
        
        if (in_array($result, ['CAPTURED', 'SUCCESS'])) {
            return 'success';
        }
        
        if (in_array($result, ['NOT CAPTURED', 'FAILED', 'CANCELLED'])) {
            return 'failed';
        }
        
        return 'pending';
    }

    /**
     * Make refund request
     */
    protected function makeRefundRequest(array $params): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post($this->baseUrl, $params);

            if (!$response->successful()) {
                throw new KnetException('Refund request failed: ' . $response->body());
            }

            // Parse response (KNET returns form-encoded or XML)
            $responseData = $this->parseResponse($response->body());

            return $responseData;
        } catch (\Exception $e) {
            Log::error('KNET Refund Error', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw new KnetException('Refund request failed: ' . $e->getMessage());
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
    public function getPaymentByTrackId(string $trackId): ?KnetPayment
    {
        return KnetPayment::where('track_id', $trackId)->first();
    }

    /**
     * Get payment by transaction ID
     */
    public function getPaymentByTransId(string $transId): ?KnetPayment
    {
        return KnetPayment::where('trans_id', $transId)->first();
    }
}

