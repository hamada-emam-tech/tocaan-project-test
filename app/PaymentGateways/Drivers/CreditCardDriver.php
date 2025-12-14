<?php


namespace App\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Str;

class CreditCardDriver implements PaymentGatewayInterface
{
    /**
     * Process a payment through the Credit Card gateway.
     *
     * @param  Payment  $payment
     * @return array{success: bool, transaction_id?: string, message?: string, data?: array}
     */
    public function process(Payment $payment): array
    {
        // Simulate API call to credit card processor
        // In production, this would integrate with real payment gateway

        // Simulate random success/failure for realistic testing
        $isSuccessful = $this->simulatePaymentProcessing();

        if ($isSuccessful) {
            return [
                'success' => true,
                'transaction_id' => 'CC-' . strtoupper(Str::random(16)),
                'message' => 'Payment processed successfully via Credit Card',
                'data' => [
                    'gateway' => 'credit_card',
                    'processor' => 'Visa/Mastercard',
                    'processed_at' => now()->toIso8601String(),
                    'amount' => $payment->amount,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment declined by Credit Card processor',
            'data' => [
                'gateway' => 'credit_card',
                'error_code' => 'CARD_DECLINED',
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
        // Simulate verification
        return [
            'success' => true,
            'status' => 'verified',
            'data' => [
                'transaction_id' => $transactionId,
                'verified_at' => now()->toIso8601String(),
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
        // Simulate refund processing
        return [
            'success' => true,
            'refund_id' => 'REF-' . strtoupper(Str::random(12)),
            'message' => 'Refund processed successfully',
        ];
    }

    /**
     * Simulate payment processing with realistic success/failure rates.
     */
    protected function simulatePaymentProcessing(): bool
    {
        // 85% success rate for realistic testing
        // Force success in testing environment to prevent flaky tests
        if (app()->environment('testing')) {
            return true;
        }

        return rand(1, 100) <= 85;
    }

    /**
     * Get a random failure reason for realistic error messages.
     */
    protected function getRandomFailureReason(): string
    {
        $reasons = [
            'Insufficient funds',
            'Card expired',
            'Invalid CVV',
            'Card blocked by issuer',
            'Transaction limit exceeded',
        ];

        return $reasons[array_rand($reasons)];
    }
}
