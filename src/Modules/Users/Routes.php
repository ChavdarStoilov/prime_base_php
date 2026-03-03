<?php

namespace App\Modules\Users;

use App\Middleware\AuthorizationMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\AuthenticationMiddleware;
use App\Modules\Users\Controller\UsersController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/users', function (RouteCollectorProxyInterface $group) {

            $group->post('/create', [UsersController::class, 'create'])
                ->setName("users.create");

            $group->get('', [UsersController::class, 'list'])
                ->setName("users.list");

            $group->get('/{uuid}', [UsersController::class, 'getUser'])
                ->setName("users.view")
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}', [UsersController::class, 'update'])
                ->setName("users.update")
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}', [UsersController::class, 'delete'])
                ->setName("users.delete")
                ->add(UuidMiddleware::class);

        })
            ->add(AuthorizationMiddleware::class)
            ->add(AuthenticationMiddleware::class);
    }
}
