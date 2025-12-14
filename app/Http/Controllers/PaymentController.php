<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderNotConfirmedException;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService, protected OrderService $orderService) {}
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);

        /** @var User $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            $payments = $this->paymentService->getAllPayments($perPage);
        } else {
            $payments = $this->paymentService->getUserPayments($user->id, $perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail((int) $request->order_id);

            /** @var User $user */
            $user = auth()->user();

            if (!$user->isAdmin() && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            $payment = $this->paymentService->processPayment(
                $order,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (OrderNotConfirmedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(string $id): JsonResponse
    {
        $payment = $this->paymentService->findPayment((int) $id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        /** @var User $user */
        $user = auth()->user();

        if (!$user->isAdmin() && $payment->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this payment',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Get payments for a specific order.
     */
    public function getByOrder(string $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail((int) $orderId);

            /** @var User $user */
            $user = auth()->user();

            if (!$user->isAdmin() && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            $payments = $this->paymentService->getOrderPayments((int) $orderId);

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Verify a payment.
     */
    public function verify(string $id): JsonResponse
    {
        $payment = $this->paymentService->findPayment((int) $id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        /** @var User $user */
        $user = auth()->user();

        if (!$user->isAdmin() && $payment->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this payment',
            ], 403);
        }

        $result = $this->paymentService->verifyPayment($payment);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
