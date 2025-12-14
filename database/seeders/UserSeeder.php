<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@tocaan.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        // Customer User
        User::create([
            'name' => 'John Doe',
            'email' => 'customer@tocaan.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CUSTOMER,
            'email_verified_at' => now(),
        ]);
    }
}
