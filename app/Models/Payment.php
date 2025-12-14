<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'status',
        'transaction_id',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'payment_method'    => PaymentMethod::class,
            'status'            => PaymentStatus::class,
            'gateway_response'  => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESSFUL;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function markAsSuccessful(string $transactionId, ?array $response = null): void
    {
        $this->update([
            'status'           => PaymentStatus::SUCCESSFUL,
            'transaction_id'   => $transactionId,
            'gateway_response' => $response,
        ]);
    }

    public function markAsFailed(?array $response = null): void
    {
        $this->update([
            'status'           => PaymentStatus::FAILED,
            'gateway_response' => $response,
        ]);
    }
}
