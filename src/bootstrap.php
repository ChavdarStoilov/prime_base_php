<?php

declare(strict_types=1);

use Slim\App;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DI\Container;
use App\Shared\Database\Database;
use App\Shared\Logger\Logger;


return function (App $app, Container $container): void {

    $database = Database::init([
        'host' => $_ENV['DB_HOST'] ?? '',
        'user' =>$_ENV['DB_USER'] ??  '',
        'port' => $_ENV['DB_PORT'] ?? '',
        'db' => $_ENV['DB_NAME'] ?? '',
        'password' => $_ENV['DB_PASS'] ?? ''
    ]);

    (require __DIR__ . '/Shared/dependencies.php')($container);

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $displayErrorDetails = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $errorMiddleware = $app->addErrorMiddleware(
        $displayErrorDetails,
        true,
        true
    );

    $errorMiddleware->setDefaultErrorHandler(
        function (Request $request, Throwable $exception) {
            $response = new Response();

            Logger::log('Unhandled Exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => (string)$request->getUri(),
                    'body' => (string)$request->getBody(),
                    'headers' => $request->getHeaders(),
                ],
            ]);

            $payload = [
                'success' => false,
                'message' => ($_ENV['APP_ENV'] ?? 'production') === 'production'
                    ? 'Internal server error'
                    : $exception->getMessage()
            ];

            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    );

    (require __DIR__ . '/Routes/api.php')($app);
};
