<?php
use Slim\App;

use App\Modules\Auth\Routes as AuthRoutes;
use App\Modules\Users\Routes as UsersRoutes;

return function (App $app) {
    $app->group('/api/v1', function (\Slim\Routing\RouteCollectorProxy $group) {

        AuthRoutes::register($group);
        UsersRoutes::register($group);

    });
};
