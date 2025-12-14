<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
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

    public function test_anyone_can_list_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_anyone_can_view_product()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Product');
    }

    public function test_admin_can_create_product()
    {
        $token = auth()->login($this->admin);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/products', [
                'name' => 'New Product',
                'price' => 299.99,
                'stock' => 50,
                'description' => 'Test description'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonPath('data.price', '299.99');

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 299.99
        ]);
    }

    public function test_customer_cannot_create_product()
    {
        $token = auth()->login($this->customer);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/products', [
                'name' => 'New Product',
                'price' => 299.99,
                'stock' => 50
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_product()
    {
        $token = auth()->login($this->admin);
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Name',
                'price' => 399.99,
                'stock' => 100
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_customer_cannot_update_product()
    {
        $token = auth()->login($this->customer);
        $product = Product::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Name'
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_product()
    {
        $token = auth()->login($this->admin);
        $product = Product::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    public function test_customer_cannot_delete_product()
    {
        $token = auth()->login($this->customer);
        $product = Product::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }
}
