<?php


namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PaymentRepository implements BaseRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with(['order', 'order.user'])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ?Payment
    {
        return Payment::with(['order', 'order.user'])->find($id);
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::with(['order', 'order.user'])->findOrFail($id);
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Model $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->fresh(['order', 'order.user']);
    }

    public function getByOrder(int $orderId): Collection
    {
        return Payment::where('order_id', $orderId)
            ->latest()
            ->get();
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with(['order', 'order.user'])
            ->where('status', $status)
            ->latest()
            ->paginate($perPage);
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with(['order', 'order.user'])
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest()
            ->paginate($perPage);
    }
}
