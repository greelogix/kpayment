<?php

namespace Greelogix\KPay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Greelogix\KPay\Models\KPayPayment;
use Greelogix\KPay\Facades\KPay;
use Greelogix\KPay\Exceptions\KPayException;

class RedirectController extends Controller
{

    /**
     * Handle payment redirect - auto-submits form to KNET
     */
    public function redirect($paymentId)
    {
        try {
            // Convert to integer if string
            $paymentId = (int) $paymentId;
            
            // Log request for production monitoring (can be disabled if needed)
            if (config('kpay.log_requests', true)) {
                Log::info('KPay Redirect: Request received', [
                    'payment_id' => $paymentId,
                ]);
            }

            $payment = KPayPayment::find($paymentId);
            
            if (!$payment) {
                // Try to find by track_id as fallback
                $payment = KPayPayment::where('track_id', (string)$paymentId)->first();
                
                if (!$payment) {
                Log::error('KPay Redirect: Payment not found', [
                    'payment_id' => $paymentId,
                ]);
                    return $this->renderError(Lang::get('kpay.redirect.payment_not_found', ['id' => $paymentId]), 404);
                }
                
                // Payment found by track_id (fallback)
            }

            // Payment found - proceed with redirect

            // Get form data
            try {
                $formData = KPay::getPaymentFormData($payment);
            } catch (KPayException $e) {
                Log::error('KPay Redirect: Failed to get form data', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                    'request_data' => $payment->request_data,
                ]);
                return $this->renderError(Lang::get('kpay.redirect.invalid_request_data', ['error' => $e->getMessage()]), 400);
            }

            // Get base URL
            try {
                $baseUrl = KPay::getBaseUrl();
            } catch (\Exception $e) {
                Log::error('KPay Redirect: Failed to get base URL', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
                return $this->renderError(Lang::get('kpay.redirect.gateway_config_error'), 500);
            }
            
            // Render payment form for auto-submission to KNET
            
            return view('kpay::payment.form', [
                'formUrl' => $baseUrl,
                'formData' => $formData,
            ]);
        } catch (KPayException $e) {
            Log::error('KPay Redirect: KPayException', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->renderError($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('KPay Redirect: Unexpected error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->renderError(Lang::get('kpay.redirect.processing_error', ['error' => $e->getMessage()]), 500);
        }
    }

    /**
     * Render error page
     */
    protected function renderError(string $message, int $statusCode = 400)
    {
        try {
            return response()->view('kpay::payment.error', [
                'error' => $message,
            ], $statusCode);
        } catch (\Exception $e) {
            // Fallback if view rendering fails
            Log::error('KPay Redirect: Failed to render error view', [
                'error' => $e->getMessage(),
                'original_message' => $message,
            ]);
            return response($message, $statusCode)
                ->header('Content-Type', 'text/plain');
        }
    }
}

