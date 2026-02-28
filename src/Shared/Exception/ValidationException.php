<?php
namespace App\Shared\Exception;

class ValidationException extends ApplicationException
{
    public function __construct(string $errorCode)
    {
        parent::__construct($errorCode, 400);
    }
}
