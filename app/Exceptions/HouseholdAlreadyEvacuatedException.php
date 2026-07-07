<?php

namespace App\Exceptions;

use Exception;

class HouseholdAlreadyEvacuatedException extends Exception
{
    public function __construct(string $message = 'Household already evacuated in this center.')
    {
        parent::__construct($message);
    }
}
