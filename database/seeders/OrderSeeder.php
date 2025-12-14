<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@tocaan.com')->first();

        if (!$customer) {
            return;
        }

        $products = [
            ['name' => 'iPhone 15 Pro', 'price' => 999.00],
            ['name' => 'MacBook Air M2', 'price' => 1199.00],
            ['name' => 'AirPods Pro', 'price' => 249.00],
            ['name' => 'iPad Air', 'price' => 599.00],
            ['name' => 'Magic Keyboard', 'price' => 299.00],
            ['name' => 'Apple Watch Series 9', 'price' => 399.00],
        ];

        // Create 15 orders with diverse data
        for ($i = 0; $i < 15; $i++) {
            $status = $this->getRandomStatus();

            $order = Order::create([
                'user_id' => $customer->id,
                'status' => $status,
                'total_amount' => 0, // Will be calculated
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            // Add 1-3 items per order
            $numItems = rand(1, 3);
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products[array_rand($products)];
                $order->items()->create([
                    'product_name' => $product['name'],
                    'quantity' => rand(1, 2),
                    'price' => $product['price'],
                ]);
            }

            $order->updateTotal();
        }
    }

    private function getRandomStatus(): string
    {
        $rand = rand(1, 100);
        if ($rand <= 50) return OrderStatus::CONFIRMED->value;
        if ($rand <= 80) return OrderStatus::PENDING->value;
        return OrderStatus::CANCELLED->value;
    }
}
