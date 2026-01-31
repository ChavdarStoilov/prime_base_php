<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();


$container = new Container();
AppFactory::setContainer($container);


$app = AppFactory::create();


(require __DIR__ . '/../src/bootstrap.php')($app, $container);


$app->run();
