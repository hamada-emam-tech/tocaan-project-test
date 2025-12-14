<?php

namespace App\Exceptions;

use Exception;

class OrderCannotBeDeletedException extends Exception
{
    protected $message = 'Cannot delete order with associated payments.';
}
