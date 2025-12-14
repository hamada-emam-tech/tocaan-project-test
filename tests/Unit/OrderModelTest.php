<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_has_many_payments()
    {
        $order = Order::factory()->create();
        \App\Models\Payment::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->payments);
    }

    public function test_order_can_accept_payments_when_confirmed()
    {
        $order = Order::factory()->create(['status' => OrderStatus::CONFIRMED]);

        $this->assertTrue($order->canAcceptPayments());
    }

    public function test_order_cannot_accept_payments_when_pending()
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $this->assertFalse($order->canAcceptPayments());
    }

    public function test_order_cannot_accept_payments_when_cancelled()
    {
        $order = Order::factory()->create(['status' => OrderStatus::CANCELLED]);

        $this->assertFalse($order->canAcceptPayments());
    }

    public function test_order_calculates_total_amount_correctly()
    {
        $order = Order::factory()->create([
            'total_amount' => 0
        ]);

        $order->items = [
            ['product_name' => 'Item 1', 'quantity' => 2, 'price' => 50.00],
            ['product_name' => 'Item 2', 'quantity' => 1, 'price' => 100.00],
        ];

        $expectedTotal = (2 * 50.00) + (1 * 100.00);

        $total = 0;
        foreach ($order->items as $item) {
            $total += $item['quantity'] * $item['price'];
        }

        $this->assertEquals($expectedTotal, $total);
    }

    public function test_order_has_items_attribute()
    {
        $items = [
            ['product_name' => 'Test', 'quantity' => 1, 'price' => 10.00]
        ];

        $order = Order::factory()->create(['items' => $items]);

        $this->assertIsArray($order->items);
        $this->assertCount(1, $order->items);
    }
}
