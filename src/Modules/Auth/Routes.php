<?php

namespace App\Modules\Auth;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Auth\Controller\Controller;



class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/auth', function (RouteCollectorProxyInterface $group) {

            $group->post('/login', [Controller::class, 'login']);
            $group->post('/refresh', [Controller::class, 'refresh']);

            $group->post('/logout', [Controller::class, 'logout'])
                ->add(JwtMiddleware::class);

        });
    }
}
