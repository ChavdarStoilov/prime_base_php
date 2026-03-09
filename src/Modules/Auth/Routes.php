<?php

namespace App\Modules\Auth;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Modules\Auth\Controller\AuthController;


class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/auth', function (RouteCollectorProxyInterface $group) {

            $group->post('/login/', [AuthController::class, 'login'])
                ->add(RateLimitMiddleware::class);

            $group->post('/refresh/', [AuthController::class, 'refresh'])
                ->add(CsrfMiddleware::class);

            $group->post('/logout/', [AuthController::class, 'logout'])
                ->add(CsrfMiddleware::class);

        });
    }
}
