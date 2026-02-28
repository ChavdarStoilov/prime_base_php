<?php
// tests/integration/Bootstrap.php

use Slim\Factory\AppFactory;
use App\Middleware\ErrorMiddleware;
use App\Middleware\JsonMiddleware;
use App\Middleware\CorsMiddleware;

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$app = AppFactory::create();

// Middleware
$app->add(new CorsMiddleware());
$app->add(new ErrorMiddleware());
$app->add(new JsonMiddleware());

// Routes
(require __DIR__ . '/../../src/Routes/api.php')($app);

return $app;
