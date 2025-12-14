<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderCannotBeDeletedException;
use App\Exceptions\OrderStatusTransitionException;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);

        /** @var User $user */
        $user = auth()->user();
        if ($user->isAdmin()) {
            $orders = $this->orderService->getAllOrders($perPage);
        } else {
            $orders = $this->orderService->getUserOrders($user->id, $perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $targetUser = $user;
        if ($user->isAdmin() && $request->has('customer_id')) {
            $targetUser = User::find($request->customer_id);
        }

        $order = $this->orderService->createOrder($targetUser, $request->items);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail($id);

            /** @var User $user */
            $user = auth()->user();
            if (!$user->isAdmin() && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail($id);

            /** @var User $user */
            $user = auth()->user();

            if (!$user->isAdmin() && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            $order = $this->orderService->updateOrder($order, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (OrderStatusTransitionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update order status (Admin only).
     */
    public function updateStatus(\App\Http\Requests\Order\UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail($id);

            if (!auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $order = $this->orderService->updateOrder($order, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (OrderStatusTransitionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->findOrderOrFail($id);

            /** @var User $user */
            $user = auth()->user();

            if (!$user->isAdmin() && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            $this->orderService->deleteOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (OrderCannotBeDeletedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
