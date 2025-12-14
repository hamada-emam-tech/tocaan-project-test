<?php


namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderCannotBeDeletedException;
use App\Exceptions\OrderStatusTransitionException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($perPage);
    }

    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByStatus($status, $perPage);
    }

    public function getUserOrders(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByUser($userId, $perPage);
    }

    public function findOrder(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    public function findOrderOrFail(int $id): Order
    {
        return $this->orderRepository->findOrFail($id);
    }

    public function createOrder(User $user, array $items): Order
    {
        return DB::transaction(function () use ($user, $items) {
            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'total_amount' => 0,
            ]);

            foreach ($items as $item) {
                if (!empty($item['product_id'])) {
                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product) {
                        $item['product_name'] = $product->name;
                        $item['price'] = $product->price;
                    }
                }

                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            $order->updateTotal();

            return $order->fresh(['user', 'items', 'payments']);
        });
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if (isset($data['status'])) {
                $newStatus = OrderStatus::from($data['status']);
                $this->validateStatusTransition($order, $newStatus);
            }

            $order = $this->orderRepository->update($order, $data);

            if (isset($data['items'])) {
                $order->items()->delete();

                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                $order->updateTotal();
            }

            return $order->fresh(['user', 'items', 'payments']);
        });
    }
    public function deleteOrder(Order $order): bool
    {
        if (!$order->canBeDeleted()) {
            throw new OrderCannotBeDeletedException(
                'Cannot delete order with associated payments.'
            );
        }

        return $this->orderRepository->delete($order);
    }

    protected function validateStatusTransition(Order $order, OrderStatus $newStatus): void
    {
        if (!$order->status->canTransitionTo($newStatus)) {
            throw new OrderStatusTransitionException(
                "Cannot transition from {$order->status->value} to {$newStatus->value}"
            );
        }
    }
}
