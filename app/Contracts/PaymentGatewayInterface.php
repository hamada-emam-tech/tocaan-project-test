<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment through the gateway driver.
     *
     * @param  Payment  $payment  The payment to process
     * @return array{success: bool, transaction_id?: string, message?: string, data?: array}
     */
    public function process(Payment $payment): array;

    /**
     * Verify a payment transaction.
     *
     * @param  string  $transactionId  The transaction ID to verify
     * @return array{success: bool, status?: string, data?: array}
     */
    public function verify(string $transactionId): array;

    /**
     * Refund a payment.
     *
     * @param  string  $transactionId  The transaction ID to refund
     * @param  float  $amount  The amount to refund
     * @return array{success: bool, refund_id?: string, message?: string}
     */
    public function refund(string $transactionId, float $amount): array;
}
