<?php


namespace App\Exceptions;

use Exception;

class OrderNotConfirmedException extends Exception
{
    protected $message = 'Payments can only be processed for confirmed orders.';
}
