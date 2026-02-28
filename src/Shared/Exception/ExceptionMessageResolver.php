<?php

namespace App\Shared\Exception;

use UnexpectedValueException;

class ExceptionMessageResolver
{
    private array $messages;

    /**
     * @param string $basePath
     * @param string $locale
     */
    public function __construct(string $basePath, string $locale)
    {
        $file = $basePath . '/messages/' . $locale . '.json';

        if (!file_exists($file)) {
            throw new UnexpectedValueException("Locale file not found.");
        }

        $this->messages = json_decode(
            file_get_contents($file),
            true
        ) ?? [];
    }

    public function resolve(string $code): string
    {
        return $this->messages[$code]
            ?? 'Unexpected error occurred.';
    }
}
