<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Order;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_belongs_to_order()
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertEquals($order->id, $payment->order->id);
    }

    public function test_payment_can_be_marked_as_successful()
    {
        $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

        $payment->markAsSuccessful('TXN123', ['gateway' => 'test']);

        $this->assertEquals(PaymentStatus::SUCCESSFUL, $payment->fresh()->status);
        $this->assertEquals('TXN123', $payment->fresh()->transaction_id);
    }

    public function test_payment_can_be_marked_as_failed()
    {
        $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

        $payment->markAsFailed(['error' => 'Insufficient funds']);

        $this->assertEquals(PaymentStatus::FAILED, $payment->fresh()->status);
    }

    public function test_payment_has_correct_fillable_attributes()
    {
        $fillable = (new Payment())->getFillable();

        $this->assertContains('order_id', $fillable);
        $this->assertContains('payment_method', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('transaction_id', $fillable);
        $this->assertContains('gateway_response', $fillable);
    }

    public function test_payment_casts_amount_to_float()
    {
        $payment = Payment::factory()->create(['amount' => '100.50']);

        $this->assertIsFloat($payment->amount);
        $this->assertEquals(100.50, $payment->amount);
    }

    public function test_payment_casts_gateway_response_to_array()
    {
        $response = ['status' => 'success', 'code' => '200'];
        $payment = Payment::factory()->create(['gateway_response' => $response]);

        $this->assertIsArray($payment->gateway_response);
        $this->assertEquals($response, $payment->gateway_response);
    }
}
