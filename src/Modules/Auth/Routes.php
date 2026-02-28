<?php

namespace App\Modules\Auth;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Auth\Controller\AuthController;



class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/auth', function (RouteCollectorProxyInterface $group) {

            $group->post('/login', [AuthController::class, 'login']);
            $group->post('/refresh', [AuthController::class, 'refresh']);

            $group->post('/logout', [AuthController::class, 'logout'])
                ->add(JwtMiddleware::class);

        });
    }
}
