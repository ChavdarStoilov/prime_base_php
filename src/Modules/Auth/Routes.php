<?php

namespace App\Modules\Auth;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Auth\Controller\Controller;
use App\Middleware\ErrorMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // група /auth
        $group->group('/auth', function (RouteCollectorProxyInterface $group) {

            // public routes
            $group->post('/login', [Controller::class, 'login']);
            $group->post('/refresh', [Controller::class, 'refresh']);

            // protected route (пример: logout изисква JWT)
            $group->post('/logout', [Controller::class, 'logout'])
                ->add(JwtMiddleware::class);

        })->add(ErrorMiddleware::class);
    }
}
