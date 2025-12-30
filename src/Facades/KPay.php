<?php

namespace Greelogix\KPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getPaymentMethods(string $platform = 'web')
 * @method static array generatePaymentForm(array $data)
 * @method static array generatePaymentUrl(array $data)
 * @method static array generatePaymentRedirectUrl(array $data, ?string $redirectRoute = null)
 * @method static bool validateResponse(array $response)
 * @method static \Greelogix\KPay\Models\KPayPayment processResponse(array $response)
 * @method static array processRefund(array $data)
 * @method static array inquiryTransaction(string $trackId)
 * @method static \Greelogix\KPay\Models\KPayPayment|null getPaymentByTrackId(string $trackId)
 * @method static \Greelogix\KPay\Models\KPayPayment|null getPaymentByTransId(string $transId)
 *
 * @see \Greelogix\KPay\Services\KPayService
 */
class KPay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'kpay';
    }
}

