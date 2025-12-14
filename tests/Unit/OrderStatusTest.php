<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_can_get_all_status_values()
    {
        $values = OrderStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('pending', $values);
        $this->assertContains('confirmed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function test_pending_can_transition_to_confirmed()
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CONFIRMED));
    }

    public function test_pending_can_transition_to_cancelled()
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_confirmed_can_transition_to_cancelled()
    {
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_cancelled_cannot_transition_to_any_status()
    {
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::CONFIRMED));
    }

    public function test_confirmed_cannot_transition_to_pending()
    {
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::PENDING));
    }

    public function test_pending_cannot_transition_to_itself()
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PENDING));
    }
}
