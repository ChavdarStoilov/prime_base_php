<?php

use Slim\App;

use App\Modules\Auth\Routes as AuthRoutes;
use App\Modules\Users\Routes as UsersRoutes;
use App\Middleware\JsonMiddleware;
use App\Middleware\ErrorMiddleware;

return function (App $app) {
    $app->group('/api/v1', function (\Slim\Routing\RouteCollectorProxy $group) {

        AuthRoutes::register($group);
        UsersRoutes::register($group);

    })->add(ErrorMiddleware::class)->add(JsonMiddleware::class);
};
