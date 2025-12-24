<?php

namespace Greelogix\KPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getPaymentMethods(string $platform = 'web')
 * @method static array generatePaymentForm(array $data)
 * @method static bool validateResponse(array $response)
 * @method static \Greelogix\KPayment\Models\KnetPayment processResponse(array $response)
 * @method static array processRefund(array $data)
 * @method static \Greelogix\KPayment\Models\KnetPayment|null getPaymentByTrackId(string $trackId)
 * @method static \Greelogix\KPayment\Models\KnetPayment|null getPaymentByTransId(string $transId)
 *
 * @see \Greelogix\KPayment\Services\KnetService
 */
class KPayment extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'kpayment';
    }
}

