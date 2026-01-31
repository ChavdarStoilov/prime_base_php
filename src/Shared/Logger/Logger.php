<?php

namespace App\Shared\Logger;

class Logger
{
    /**
     *
     * @param string $message Основно съобщение
     * @param mixed  $data    Допълнителни данни (string, array, object)
     * @return void
     */
    public static function log(string $message, $data = null): void
    {
        $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$isDebug) {
            return;
        }

        $logFile = $_ENV['LOG_FILE_PATH'] ?? null;
        if (!$logFile) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');

        if (is_array($data) || is_object($data)) {
            $dataString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($data !== null) {
            $dataString = (string) $data;
        } else {
            $dataString = '';
        }

        $logEntry = "[$timestamp] $message";
        if ($dataString !== '') {
            $logEntry .= " | Data: " . $dataString;
        }
        $logEntry .= PHP_EOL;

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
