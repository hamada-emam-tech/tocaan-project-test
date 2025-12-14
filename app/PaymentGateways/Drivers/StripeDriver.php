<?php

namespace App\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Str;

class StripeDriver implements PaymentGatewayInterface
{
    /**
     * Process a payment through Stripe.
     *
     * @param  Payment  $payment
     * @return array{success: bool, transaction_id?: string, message?: string, data?: array}
     */
    public function process(Payment $payment): array
    {
        // Simulate Stripe API integration
        $isSuccessful = $this->simulatePaymentProcessing();

        if ($isSuccessful) {
            return [
                'success' => true,
                'transaction_id' => 'ch_' . Str::random(24),
                'message' => 'Payment processed successfully via Stripe',
                'data' => [
                    'gateway' => 'stripe',
                    'payment_intent' => 'pi_' . Str::random(24),
                    'processed_at' => now()->toIso8601String(),
                    'amount' => $payment->amount,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Stripe payment failed',
            'data' => [
                'gateway' => 'stripe',
                'error_code' => 'card_error',
                'decline_code' => $this->getRandomDeclineCode(),
                'reason' => $this->getRandomFailureReason(),
            ],
        ];
    }

    /**
     * Verify a payment transaction.
     *
     * @param  string  $transactionId
     * @return array{success: bool, status?: string, data?: array}
     */
    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'verified',
            'data' => [
                'transaction_id' => $transactionId,
                'verified_at' => now()->toIso8601String(),
                'gateway' => 'stripe',
                'charge_status' => 'succeeded',
            ],
        ];
    }

    /**
     * Refund a payment.
     *
     * @param  string  $transactionId
     * @param  float  $amount
     * @return array{success: bool, refund_id?: string, message?: string}
     */
    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => true,
            'refund_id' => 're_' . Str::random(24),
            'message' => 'Stripe refund processed successfully',
        ];
    }

    /**
     * Simulate payment processing.
     */
    protected function simulatePaymentProcessing(): bool
    {
        // 88% success rate
        return rand(1, 100) <= 88;
    }

    /**
     * Get a random Stripe decline code.
     */
    protected function getRandomDeclineCode(): string
    {
        $codes = [
            'insufficient_funds',
            'card_declined',
            'expired_card',
            'incorrect_cvc',
            'processing_error',
        ];

        return $codes[array_rand($codes)];
    }

    /**
     * Get a random failure reason.
     */
    protected function getRandomFailureReason(): string
    {
        $reasons = [
            'Your card has insufficient funds',
            'Your card was declined',
            'Your card has expired',
            'Your card\'s security code is incorrect',
            'An error occurred while processing your card',
        ];

        return $reasons[array_rand($reasons)];
    }
}
