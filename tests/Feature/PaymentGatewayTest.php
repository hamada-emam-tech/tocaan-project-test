<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_available_gateways()
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $token = auth()->login($user);
        config(['payment.gateways.credit_card' => ['api_key' => 'test_key']]);
        config(['payment.default' => 'credit_card']);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payment-gateways');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'code' => 'credit_card',
                'name' => 'Credit Card',
                'is_default' => true
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        foreach ($data as $gateway) {
            $this->assertArrayHasKey('code', $gateway);
            $this->assertArrayHasKey('name', $gateway);
            $this->assertArrayHasKey('is_default', $gateway);

            $this->assertArrayNotHasKey('api_key', $gateway);
            $this->assertArrayNotHasKey('secret', $gateway);
            $this->assertArrayNotHasKey('credentials', $gateway);
        }
    }
}
