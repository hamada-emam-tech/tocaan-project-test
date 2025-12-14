<?php


namespace App\Exceptions;

use Exception;

class OrderStatusTransitionException extends Exception
{
    protected $message = 'Invalid order status transition.';
}
