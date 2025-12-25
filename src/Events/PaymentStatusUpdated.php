<?php

namespace Greelogix\KPayment\Events;

use Greelogix\KPayment\Models\KnetPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated
{
    use Dispatchable, SerializesModels;

    public KnetPayment $payment;

    /**
     * Create a new event instance.
     */
    public function __construct(KnetPayment $payment)
    {
        $this->payment = $payment;
    }
}


