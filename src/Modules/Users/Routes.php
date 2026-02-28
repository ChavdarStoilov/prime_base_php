<?php

namespace App\Modules\Users;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Users\Controller\UsersController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/users', function (RouteCollectorProxyInterface $group) {

            $group->post('/create', [UsersController::class, 'create']);

            $group->get('/', [UsersController::class, 'list']);

            $group->get('/{uuid}', [UsersController::class, 'getUser'])
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}', [UsersController::class, 'update'])
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}', [UsersController::class, 'delete'])
                ->add(UuidMiddleware::class);

        })->add(JwtMiddleware::class);
    }
}
