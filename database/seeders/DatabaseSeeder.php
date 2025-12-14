<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            ProductSeeder::class,
            UserSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
