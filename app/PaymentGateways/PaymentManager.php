<?php

namespace App\PaymentGateways;

use App\PaymentGateways\Drivers\CreditCardDriver;
use App\PaymentGateways\Drivers\PayPalDriver;
use App\PaymentGateways\Drivers\StripeDriver;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    protected function createCreditCardDriver(): CreditCardDriver
    {
        return new CreditCardDriver();
    }

    protected function createPaypalDriver(): PayPalDriver
    {
        return new PayPalDriver();
    }

    protected function createStripeDriver(): StripeDriver
    {
        return new StripeDriver();
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default');
    }
}
