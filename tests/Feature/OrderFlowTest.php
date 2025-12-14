<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Enums\UserRole;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
    }

    public function test_customer_can_create_order()
    {
        $token = auth()->login($this->customer);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_name' => 'Test Product',
                        'quantity' => 2,
                        'price' => 100.00
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_amount', "200.00")
            ->assertJsonPath('data.status', OrderStatus::PENDING->value);
    }

    public function test_admin_can_create_order_for_customer()
    {
        $token = auth()->login($this->admin);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_name' => 'Admin Created Product',
                        'quantity' => 1,
                        'price' => 50.00
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $this->customer->id)
            ->assertJsonPath('data.total_amount', "50.00");
    }

    public function test_customer_can_create_order_with_product_id()
    {
        $product = \App\Models\Product::create([
            'name' => 'Db Product',
            'price' => 50.00,
            'stock' => 10,
            'description' => 'desc'
        ]);

        $token = auth()->login($this->customer);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.items.0.product_name', 'Db Product')
            ->assertJsonPath('data.total_amount', "100.00");
    }

    public function test_customer_can_view_own_orders()
    {
        $token = auth()->login($this->customer);
        Order::factory()->count(3)->create(['user_id' => $this->customer->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_admin_can_view_all_orders()
    {
        $token = auth()->login($this->admin);
        Order::factory()->count(3)->create(['user_id' => $this->customer->id]);
        $otherCustomer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        Order::factory()->count(2)->create(['user_id' => $otherCustomer->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data');
    }

    public function test_admin_can_update_status()
    {
        $token = auth()->login($this->admin);
        $order = Order::factory()->create(['user_id' => $this->customer->id, 'status' => OrderStatus::PENDING]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => OrderStatus::CONFIRMED->value
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', OrderStatus::CONFIRMED->value);
    }

    public function test_customer_cannot_update_status()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create(['user_id' => $this->customer->id, 'status' => OrderStatus::PENDING]);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => OrderStatus::CONFIRMED->value
            ])
            ->assertStatus(403);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/orders/{$order->id}", [
                'status' => OrderStatus::CONFIRMED->value,
                'items' => [['product_name' => 'x', 'quantity' => 1, 'price' => 10]]
            ])
            ->assertStatus(200);

        $this->assertEquals(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_customer_can_view_own_order()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_customer_cannot_view_other_customer_order()
    {
        $token = auth()->login($this->customer);
        $otherCustomer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $order = Order::factory()->create(['user_id' => $otherCustomer->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_customer_can_update_own_order_items()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::PENDING
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/orders/{$order->id}", [
                'items' => [
                    [
                        'product_name' => 'Updated Product',
                        'quantity' => 5,
                        'price' => 50.00
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $this->assertEquals('250.00', $order->fresh()->total_amount);
    }

    public function test_customer_can_delete_own_order_without_payments()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_order_with_payments()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED
        ]);

        // Create a payment for the order
        \App\Models\Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
