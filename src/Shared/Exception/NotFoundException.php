<?php
namespace App\Shared\Exception;

class NotFoundException extends ApplicationException
{
    public function __construct(string $errorCode)
    {
        parent::__construct($errorCode, 404);
    }
}
