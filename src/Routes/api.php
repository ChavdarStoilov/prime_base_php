<?php

use Slim\App;

use App\Modules\Auth\Routes as AuthRoutes;
use App\Modules\Users\Routes as UsersRoutes;
use App\Modules\Permissions\Routes as PermissionsRoutes;
use App\Modules\Roles\Routes as RolesRoutes;
use App\Middleware\JsonMiddleware;
use App\Middleware\ErrorMiddleware;
use App\Middleware\CorsMiddleware;

return function (App $app) {


    $app->add(new CorsMiddleware());
    $app->add(ErrorMiddleware::class);
    $app->add(new JsonMiddleware());


    $app->group('/api/v1', function (\Slim\Routing\RouteCollectorProxy $group) {

        AuthRoutes::register($group);
        UsersRoutes::register($group);
        PermissionsRoutes::register($group);
        RolesRoutes::register($group);


    });

    $app->options('/{routes:.+}', function ($request, $response) {
        return $response->withStatus(200);
    });
};
