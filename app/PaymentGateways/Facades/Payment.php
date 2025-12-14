<?php

namespace App\PaymentGateways\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method array process(Payment $payment)
 * @method array verify(string $transactionId)
 * @method array refund(string $transactionId, float $amount)
 */
class Payment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'payment';
    }
}
