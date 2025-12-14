<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
    ];

    protected $appends = [
        'formatted_total',
        'formatted_status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'status'       => OrderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include orders with a given status.
     */
    public function scopeStatus($query, $status)
    {
        if ($status instanceof OrderStatus) {
            $status = $status->value;
        }
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    /**
     * Scope a query to only include confirmed orders.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', OrderStatus::CONFIRMED);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function canBeDeleted(): bool
    {
        return !$this->hasPayments();
    }

    public function canAcceptPayments(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }

    public function calculateTotal(): float
    {
        return (float) $this->items->sum(function (OrderItem $item) {
            return $item->quantity * $item->price;
        });
    }

    public function updateTotal(): void
    {
        $this->total_amount = $this->calculateTotal();
        $this->save();
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount ?? 0, 2);
    }

    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status->value);
    }
}
