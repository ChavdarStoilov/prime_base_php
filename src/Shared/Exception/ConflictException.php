<?php

namespace App\Shared\Exception;

class ConflictException extends ApplicationException
{
    public function __construct(string $errorCode)
    {
        parent::__construct($errorCode, 409);
    }
}
