<?php


namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\OrderNotConfirmedException;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\Facades\Payment as FacadesPayment;
use App\PaymentGateways\PaymentManager;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(protected PaymentRepository $paymentRepository, protected PaymentManager $paymentManager) {}

    public function getAllPayments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($perPage);
    }

    public function getUserPayments(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->getByUser($userId, $perPage);
    }

    public function getOrderPayments(int $orderId): Collection
    {
        return $this->paymentRepository->getByOrder($orderId);
    }

    public function getPaymentsByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->getByStatus($status, $perPage);
    }

    public function findPayment(int $id): ?Payment
    {
        return $this->paymentRepository->find($id);
    }

    public function processPayment(Order $order, float $amount): Payment
    {
        if (!$order->canAcceptPayments()) {
            throw new OrderNotConfirmedException(
                'Payments can only be processed for confirmed orders.'
            );
        }

        $paidAmount = $order->payments()
            ->whereIn('status', [PaymentStatus::SUCCESSFUL, PaymentStatus::PENDING])
            ->sum('amount');

        $remaining = $order->total_amount - $paidAmount;

        if ($remaining <= 0) {
            throw new \InvalidArgumentException('Order is already fully paid.');
        }

        if ($amount > $remaining) {
            throw new \InvalidArgumentException("Payment amount {$amount} exceeds remaining balance of {$remaining}.");
        }

        return DB::transaction(function () use ($order, $amount) {
            $payment = $this->paymentRepository->create([
                'order_id' => $order->id,
                'payment_method' => config('payment.default'),
                'amount' => $amount,
                'status' => PaymentStatus::PENDING,
            ]);
            $this->processPaymentThroughGateway($payment);
            return $payment->fresh(['order', 'order.user']);
        });
    }

    protected function processPaymentThroughGateway(Payment $payment): void
    {
        try {
            $result = FacadesPayment::process($payment);
            if ($result['success']) {
                $payment->markAsSuccessful(
                    $result['transaction_id'],
                    $result['data'] ?? null
                );

                Log::info('Payment processed successfully', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $result['transaction_id'],
                ]);
            } else {
                $payment->markAsFailed($result['data'] ?? null);

                Log::warning('Payment failed', [
                    'payment_id' => $payment->id,
                    'reason' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            $payment->markAsFailed([
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            Log::error('Payment processing exception', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function verifyPayment(Payment $payment): array
    {
        if (!$payment->transaction_id) {
            return [
                'success' => false,
                'message' => 'No transaction ID available for verification',
            ];
        }

        return FacadesPayment::verify($payment->transaction_id);
    }

    public function refundPayment(Payment $payment, ?float $amount = null): array
    {
        if (!$payment->isSuccessful()) {
            return [
                'success' => false,
                'message' => 'Can only refund successful payments',
            ];
        }

        if (!$payment->transaction_id) {
            return [
                'success' => false,
                'message' => 'No transaction ID available for refund',
            ];
        }

        $refundAmount = $amount ?? $payment->amount;
        return FacadesPayment::refund($payment->transaction_id, $refundAmount);
    }
}
