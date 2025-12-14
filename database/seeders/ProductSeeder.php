<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'MacBook Pro M3', 'price' => 1599.00, 'stock' => 50, 'description' => 'Latest Apple laptop.'],
            ['name' => 'Dell XPS 15', 'price' => 1299.00, 'stock' => 30, 'description' => 'Powerful Windows laptop.'],
            ['name' => 'Sony WH-1000XM5', 'price' => 349.99, 'stock' => 100, 'description' => 'Noise cancelling headphones.'],
            ['name' => 'Logitech MX Master 3S', 'price' => 99.99, 'stock' => 200, 'description' => 'Ergonomic mouse.'],
            ['name' => 'Keychron Q1 Pro', 'price' => 199.00, 'stock' => 75, 'description' => 'Mechanical keyboard.'],
        ];

        foreach ($products as $p) {
            Product::create($p);
        }
    }
}
