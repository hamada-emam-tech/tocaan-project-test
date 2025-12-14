<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Enums\UserRole;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_cannot_pay_for_pending_order()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 100.00
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 100.00
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Payments can only be processed for confirmed orders.');
    }

    public function test_can_pay_for_confirmed_order_via_credit_card()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 100.00
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::CREDIT_CARD->value
        ]);
    }

    public function test_cannot_overpay_order()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        // First payment (Full)
        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 100.00
            ])
            ->assertStatus(201);

        // Second payment (Overpay)
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 10.00
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Order is already fully paid.');
    }

    public function test_can_make_partial_payments()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        // First partial payment
        $response1 = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 60.00
            ]);

        $response1->assertStatus(201);

        // Second partial payment
        $response2 = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 40.00
            ]);

        $response2->assertStatus(201);

        // Verify both payments exist
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 60.00
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 40.00
        ]);
    }

    public function test_cannot_pay_for_cancelled_order()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CANCELLED,
            'total_amount' => 100.00
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => PaymentMethod::CREDIT_CARD->value,
                'amount' => 100.00
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Payments can only be processed for confirmed orders.');
    }

    public function test_customer_can_view_own_payments()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED
        ]);

        \App\Models\Payment::factory()->count(3)->create(['order_id' => $order->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_customer_cannot_view_other_customer_payments()
    {
        $token = auth()->login($this->customer);
        $otherCustomer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $otherOrder = Order::factory()->create(['user_id' => $otherCustomer->id]);

        \App\Models\Payment::factory()->count(2)->create(['order_id' => $otherOrder->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_admin_can_view_all_payments()
    {
        $token = auth()->login($this->admin);

        $order1 = Order::factory()->create(['user_id' => $this->customer->id]);
        $order2 = Order::factory()->create(['user_id' => $this->admin->id]);

        \App\Models\Payment::factory()->count(2)->create(['order_id' => $order1->id]);
        \App\Models\Payment::factory()->count(3)->create(['order_id' => $order2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data');
    }

    public function test_can_view_payment_details()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create(['user_id' => $this->customer->id]);
        $payment = \App\Models\Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 150.00
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', '150.00');
    }

    public function test_can_verify_payment()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create(['user_id' => $this->customer->id]);
        $payment = \App\Models\Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::SUCCESSFUL
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/payments/{$payment->id}/verify");

        $response->assertStatus(200);
    }

    public function test_payment_fails_with_invalid_payment_method()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => 'invalid_method',
                'amount' => 100.00
            ]);

        $response->assertStatus(422);
    }

    public function test_can_get_order_payment_history()
    {
        $token = auth()->login($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED
        ]);

        \App\Models\Payment::factory()->count(4)->create(['order_id' => $order->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/orders/{$order->id}/payments");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }
}
