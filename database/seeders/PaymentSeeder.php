<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::where('status', OrderStatus::CONFIRMED)->get();

        foreach ($orders as $order) {
            // 80% chance of having a payment attempt
            if (rand(1, 100) > 80) continue;

            $method = $this->getRandomMethod();
            $status = $this->getRandomStatus();

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $method,
                'amount' => $order->total_amount,
                'status' => $status,
                'transaction_id' => $status === PaymentStatus::SUCCESSFUL ? Str::uuid()->toString() : null,
                'gateway_response' => [
                    'message' => 'Simulated payment via seeder',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }
    }

    private function getRandomMethod(): string
    {
        $methods = PaymentMethod::values();
        return $methods[array_rand($methods)];
    }

    private function getRandomStatus(): PaymentStatus
    {
        $rand = rand(1, 100);
        if ($rand <= 70) return PaymentStatus::SUCCESSFUL;
        if ($rand <= 90) return PaymentStatus::FAILED;
        return PaymentStatus::PENDING;
    }
}
