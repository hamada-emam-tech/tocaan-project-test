<?php


namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class OrderRepository implements BaseRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['user', 'items', 'payments'])->latest();

        return app(\Illuminate\Pipeline\Pipeline::class)
            ->send($query)
            ->through([
                \App\Pipelines\Orders\Filters::class,
            ])
            ->thenReturn()
            ->paginate($perPage);
    }

    public function find(int $id): ?Order
    {
        return Order::with(['user', 'items', 'payments'])->find($id);
    }

    public function findOrFail(int $id): Order
    {
        return Order::with(['user', 'items', 'payments'])->findOrFail($id);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Model $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(['user', 'items', 'payments']);
    }

    public function delete(Order $order): bool
    {
        return (bool) $order->delete();
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['user', 'items', 'payments'])
            ->status($status)
            ->latest()
            ->paginate($perPage);
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['items', 'payments'])
            ->where('user_id', $userId)
            ->latest();

        return app(\Illuminate\Pipeline\Pipeline::class)
            ->send($query)
            ->through([
                \App\Pipelines\Orders\Filters::class,
            ])
            ->thenReturn()
            ->paginate($perPage);
    }
}
