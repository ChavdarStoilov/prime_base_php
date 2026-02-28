<?php

declare(strict_types=1);

use Slim\App;
use DI\Container;


return function (App $app, Container $container): void {


    (require __DIR__ . '/Shared/dependencies.php')($container);

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    (require __DIR__ . '/Routes/api.php')($app);
};
