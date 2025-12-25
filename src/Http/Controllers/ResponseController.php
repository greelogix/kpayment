<?php

namespace Greelogix\KPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Greelogix\KPayment\Facades\KPayment;
use Greelogix\KPayment\Events\PaymentStatusUpdated;
use Greelogix\KPayment\Exceptions\KnetException;

class ResponseController extends Controller
{
    /**
     * Handle payment response from KNET
     */
    public function handle(Request $request)
    {
        try {
            $response = $request->all();
            
            // Log incoming response for debugging
            Log::info('KNET Payment Response Received', [
                'response' => $response,
            ]);
            
            // Process the response
            $payment = KPayment::processResponse($response);
            
            // Fire event
            event(new PaymentStatusUpdated($payment));
            
            // Redirect based on payment status
            if ($payment && $payment->isSuccessful()) {
                return $this->handleSuccess($payment, $response);
            } else {
                return $this->handleFailure($payment, $response);
            }
        } catch (KnetException $e) {
            Log::error('KNET Response Error', [
                'response' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleError($e);
        } catch (\Exception $e) {
            Log::error('KNET Response Unexpected Error', [
                'response' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleError(new KnetException(__('kpayment.response.unexpected_error')));
        }
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
        } elseif (Route::has('kpayment.success')) {
            $successUrl = route('kpayment.success');
        } else {
            $successUrl = url('/payment/success');
        }
        
        return redirect($successUrl)->with([
            'payment' => $payment,
            'message' => __('kpayment.response.success'),
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
        } elseif (Route::has('kpayment.error')) {
            $errorUrl = route('kpayment.error');
        } else {
            $errorUrl = url('/payment/error');
        }
        
        return redirect($errorUrl)->with([
            'payment' => $payment,
            'message' => __('kpayment.response.failed'),
        ]);
    }

    /**
     * Handle error
     */
    protected function handleError($exception)
    {
        // Priority: route > default URL
        if (Route::has('kpayment.error')) {
            $errorUrl = route('kpayment.error');
        } else {
            $errorUrl = url('/payment/error');
        }
        
        $errorMessage = $exception instanceof KnetException 
            ? $exception->getMessage() 
            : __('kpayment.response.error');
        
        return redirect($errorUrl)->with([
            'error' => $errorMessage,
        ]);
    }
}


