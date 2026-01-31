<?php

declare(strict_types=1);

use Slim\App;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DI\Container;
use App\Shared\Database\Database;
use App\Shared\Logger\Logger;
use App\Middleware\ErrorMiddleware;


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

//    $errorMiddleware->setDefaultErrorHandler(ErrorMiddleware::class);
//
//
//    );

    (require __DIR__ . '/Routes/api.php')($app);
};
