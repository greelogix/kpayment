<?php

namespace Greelogix\KPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
            
            // Process the response
            $payment = KPayment::processResponse($response);
            
            // Fire event
            event(new PaymentStatusUpdated($payment));
            
            // Redirect based on payment status
            if ($payment->isSuccessful()) {
                return $this->handleSuccess($payment, $response);
            } else {
                return $this->handleFailure($payment, $response);
            }
        } catch (KnetException $e) {
            \Log::error('KNET Response Error', [
                'response' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            
            return $this->handleError($e);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccess($payment, array $response)
    {
        // You can customize this redirect URL
        $successUrl = $payment->udf1 ?? route('kpayment.success');
        
        return redirect($successUrl)->with([
            'payment' => $payment,
            'message' => 'Payment completed successfully',
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailure($payment, array $response)
    {
        // You can customize this redirect URL
        $errorUrl = $payment->udf2 ?? route('kpayment.error');
        
        return redirect($errorUrl)->with([
            'payment' => $payment,
            'message' => 'Payment failed',
        ]);
    }

    /**
     * Handle error
     */
    protected function handleError($exception)
    {
        $errorUrl = route('kpayment.error');
        
        return redirect($errorUrl)->with([
            'error' => $exception->getMessage(),
        ]);
    }
}

