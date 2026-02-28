<?php

namespace App\Shared\Exception;

class UnauthorizedException extends ApplicationException
{
    public function __construct(string $errorCode)
    {
        parent::__construct($errorCode, 401);
    }
}
