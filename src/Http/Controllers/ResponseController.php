<?php

namespace Greelogix\KPay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Greelogix\KPay\Facades\KPay;
use Greelogix\KPay\Events\PaymentStatusUpdated;
use Greelogix\KPay\Exceptions\KPayException;

class ResponseController extends Controller
{
    /**
     * Handle payment response from KPAY
     * According to KPAY documentation, responses can come via:
     * 1. POST with encrypted body (server-to-server callback) - handled by GetHandlerResponse.php
     * 2. GET with query parameters (user redirect) - handled by result.php
     * Both methods are supported
     */
    public function handle(Request $request)
    {
        try {
            // Check if this is an encrypted server-to-server callback (POST with raw body)
            // As per GetHandlerResponse.php: reads from php://input and processes raw bytes
            if ($request->isMethod('POST')) {
                // Read raw request body (matching GetHandlerResponse.php line 5)
                $requestBody = file_get_contents('php://input');
                $resTranData = "";
                
                // Process raw body as per GetHandlerResponse.php lines 7-10
                if (strlen($requestBody) > 0) {
                    $byteArray = unpack("C*", $requestBody);
                    $resTranData = implode(array_map("chr", $byteArray));
                }
                
                // If we have data, treat as encrypted callback (matching GetHandlerResponse.php line 33)
                if (!empty($resTranData)) {
                    return $this->handleEncryptedCallback($resTranData);
                }
            }
            
            // Handle regular GET/POST with query parameters (user redirect)
            $response = $request->all();
            
            // Handle KPAY validation/ping requests (empty or test requests)
            $hasPaymentParams = isset($response['trackid']) || isset($response['trackId']) || 
                               isset($response['paymentid']) || isset($response['PaymentID']) ||
                               isset($response['result']) || isset($response['Result']) ||
                               isset($response['hash']) || isset($response['Hash']);
            
            // If no payment parameters, treat as validation request
            if (!$hasPaymentParams) {
                Log::info('KPAY Response URL Validation Request', [
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'params' => array_keys($response),
                ]);
                
                return response('OK', 200)
                    ->header('Content-Type', 'text/plain');
            }
            
            // Log incoming response for debugging
            Log::info('KPAY Payment Response Received', [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'response' => $response,
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Process the response
            $payment = KPay::processResponse($response);
            
            // Fire event for external integration
            event(new PaymentStatusUpdated($payment));
            
            // Redirect based on payment status
            if ($payment && $payment->isSuccessful()) {
                return $this->handleSuccess($payment, $response);
            } else {
                return $this->handleFailure($payment, $response);
            }
        } catch (KPayException $e) {
            Log::error('KPAY Response Error', [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'response' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleError($e);
        } catch (\Exception $e) {
            Log::error('KPAY Response Unexpected Error', [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'response' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleError(new KPayException(__('kpay.response.unexpected_error')));
        }
    }

    /**
     * Handle encrypted server-to-server callback (as per GetHandlerResponse.php)
     * Decrypts the data and returns redirect URL for KPAY to send user to
     */
    protected function handleEncryptedCallback(string $encryptedData): \Illuminate\Http\Response
    {
        try {
            $resourceKey = config('kpay.resource_key', '');
            
            if (empty($resourceKey)) {
                Log::error('KPAY Encrypted Callback: Resource key not configured');
                return response('', 200);
            }
            
            // Decrypt the encrypted transaction response
            $decryptedData = $this->decrypt($encryptedData, $resourceKey);
            
            if (empty($decryptedData)) {
                Log::error('KPAY Encrypted Callback: Failed to decrypt data');
                return response('', 200);
            }
            
            // Parse decrypted data to extract parameters
            parse_str($decryptedData, $responseParams);
            
            // Process the response (update payment record)
            if (!empty($responseParams)) {
                try {
                    $payment = KPay::processResponse($responseParams);
                    event(new PaymentStatusUpdated($payment));
                    
                    Log::info('KPAY Encrypted Callback: Payment processed', [
                        'track_id' => $responseParams['trackid'] ?? null,
                        'result' => $responseParams['result'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('KPAY Encrypted Callback: Failed to process payment', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Build redirect URL with decrypted data (as per GetHandlerResponse.php)
            // This tells KPAY where to redirect the user
            $redirectUrl = url('/kpay/response?' . $decryptedData);
            
            Log::info('KPAY Encrypted Callback: Decrypted and returning redirect URL', [
                'redirect_url' => $redirectUrl,
            ]);
            
            // Return redirect response as per KPAY reference code format
            // KPAY will redirect the user to this URL
            return response('REDIRECT=' . $redirectUrl, 200)
                ->header('Content-Type', 'text/plain');
                
        } catch (\Exception $e) {
            Log::error('KPAY Encrypted Callback Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response('', 200);
        }
    }

    /**
     * Decrypt encrypted data using AES-128-CBC (as per GetHandlerResponse.php)
     */
    protected function decrypt(string $code, string $key): string
    {
        $code = $this->hex2ByteArray(trim($code));
        $code = $this->byteArray2String($code);
        $iv = $key;
        $code = base64_encode($code);
        $decrypted = openssl_decrypt($code, 'AES-128-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
        return $this->pkcs5_unpad($decrypted);
    }

    /**
     * Convert hex string to byte array
     */
    protected function hex2ByteArray(string $hexString): array
    {
        $string = hex2bin($hexString);
        return unpack('C*', $string);
    }

    /**
     * Convert byte array to string
     */
    protected function byteArray2String(array $byteArray): string
    {
        $chars = array_map("chr", $byteArray);
        return join($chars);
    }

    /**
     * Remove PKCS5 padding
     */
    protected function pkcs5_unpad(string $text): string
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccess($payment, array $response)
    {
        // You can customize this redirect URL
        // Priority: udf1 field > route > default URL
        if (!empty($payment->udf1)) {
            $successUrl = $payment->udf1;
        } elseif (Route::has('kpay.success')) {
            $successUrl = route('kpay.success');
        } else {
            $successUrl = url('/payment/success');
        }
        
        return redirect($successUrl)->with([
            'payment' => $payment,
            'message' => __('kpay.response.success'),
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailure($payment, array $response)
    {
        // You can customize this redirect URL
        // Priority: udf2 field > route > default URL
        if (!empty($payment->udf2)) {
            $errorUrl = $payment->udf2;
        } elseif (Route::has('kpay.error')) {
            $errorUrl = route('kpay.error');
        } else {
            $errorUrl = url('/payment/error');
        }
        
        return redirect($errorUrl)->with([
            'payment' => $payment,
            'message' => __('kpay.response.failed'),
        ]);
    }

    /**
     * Handle error
     */
    protected function handleError($exception)
    {
        // Priority: route > default URL
        if (Route::has('kpay.error')) {
            $errorUrl = route('kpay.error');
        } else {
            $errorUrl = url('/payment/error');
        }
        
        $errorMessage = $exception instanceof KPayException 
            ? $exception->getMessage() 
            : __('kpay.response.error');
        
        return redirect($errorUrl)->with([
            'error' => $errorMessage,
        ]);
    }
}


