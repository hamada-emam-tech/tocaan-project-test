<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_can_get_all_payment_status_values()
    {
        $values = PaymentStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('pending', $values);
        $this->assertContains('successful', $values);
        $this->assertContains('failed', $values);
    }

    public function test_payment_status_enum_has_correct_values()
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
        $this->assertEquals('successful', PaymentStatus::SUCCESSFUL->value);
        $this->assertEquals('failed', PaymentStatus::FAILED->value);
    }
}
