<?php


namespace App\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Str;

class PayPalDriver implements PaymentGatewayInterface
{
    public function __construct() {}

    /**
     * Process a payment through PayPal.
     *
     * @param  Payment  $payment
     * @return array{success: bool, transaction_id?: string, message?: string, data?: array}
     */
    public function process(Payment $payment): array
    {
        // Simulate PayPal API integration
        $isSuccessful = $this->simulatePaymentProcessing();

        if ($isSuccessful) {
            return [
                'success' => true,
                'transaction_id' => 'PP-' . strtoupper(Str::random(20)),
                'message' => 'Payment processed successfully via PayPal',
                'data' => [
                    'gateway' => 'paypal',
                    'payer_email' => 'customer@example.com',
                    'processed_at' => now()->toIso8601String(),
                    'amount' => $payment->amount,
                    'currency' => 'USD',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'PayPal payment failed',
            'data' => [
                'gateway' => 'paypal',
                'error_code' => 'PAYPAL_ERROR',
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
                'gateway' => 'paypal',
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
            'refund_id' => 'PPREF-' . strtoupper(Str::random(15)),
            'message' => 'PayPal refund processed successfully',
        ];
    }

    /**
     * Simulate payment processing with realistic success/failure rates.
     */
    protected function simulatePaymentProcessing(): bool
    {
        // 90% success rate (PayPal is generally more reliable)
        return rand(1, 100) <= 90;
    }

    /**
     * Get a random failure reason.
     */
    protected function getRandomFailureReason(): string
    {
        $reasons = [
            'PayPal account not verified',
            'Insufficient PayPal balance',
            'Payment declined by PayPal',
            'Account temporarily restricted',
        ];

        return $reasons[array_rand($reasons)];
    }
}
