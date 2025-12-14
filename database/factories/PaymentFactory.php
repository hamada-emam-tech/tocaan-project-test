<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'payment_method' => PaymentMethod::CREDIT_CARD, // Default
            'transaction_id' => $this->faker->uuid(),
            'status' => PaymentStatus::PENDING,
        ];
    }
}
