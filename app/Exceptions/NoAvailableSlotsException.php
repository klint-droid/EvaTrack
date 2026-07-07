<?php

namespace App\Exceptions;

use Exception;

class NoAvailableSlotsException extends Exception
{
    public function __construct(string $message = 'Unit does not have enough available slots.')
    {
        parent::__construct($message);
    }
}
