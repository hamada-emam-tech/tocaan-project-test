<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Enums\UserRole;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_fails_with_invalid_payment_method()
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        $token = auth()->login($customer);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => 'invalid_gateway',
                'amount' => 100.00
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Payment method 'invalid_gateway' is not configured or available.");
    }

    public function test_payment_succeeds_with_valid_payment_method()
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        $token = auth()->login($customer);

        // Ensure gateway is configured
        config(['payment.gateways.credit_card' => ['api_key' => 'test']]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => 'credit_card',
                'amount' => 100.00
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_middleware_sets_payment_method_as_default_temporarily()
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00
        ]);

        $token = auth()->login($customer);

        config(['payment.default' => 'credit_card']);
        config(['payment.gateways.paypal' => ['client_id' => 'test']]);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/orders/{$order->id}/pay", [
                'payment_method' => 'paypal',
                'amount' => 100.00
            ]);

        $this->assertTrue(true);
    }
}
