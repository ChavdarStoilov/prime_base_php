<?php

namespace App\Shared\Exception;

abstract class ApplicationException extends \RuntimeException
{
    protected string $errorCode;
    protected int $httpStatus;

    public function __construct(string $errorCode, int $httpStatus)
    {
        parent::__construct($errorCode);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
