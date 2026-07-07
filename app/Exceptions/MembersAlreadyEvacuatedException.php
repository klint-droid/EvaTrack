<?php

namespace App\Exceptions;

use Exception;

class MembersAlreadyEvacuatedException extends Exception
{
    public function __construct(string $names)
    {
        parent::__construct("The following member(s) are already actively evacuated: {$names}. Please check them out first.");
    }
}
