<?php

namespace App\Exceptions;

use Exception;

class NoCenterAssignedException extends Exception
{
    public function __construct(string $message = 'No evacuation center specified or assigned.')
    {
        parent::__construct($message);
    }
}
