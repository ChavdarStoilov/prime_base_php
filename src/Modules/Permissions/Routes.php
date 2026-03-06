<?php

namespace App\Modules\Permissions;

use App\Middleware\AuthorizationMiddleware;
use App\Middleware\CsrfMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\AuthenticationMiddleware;
use App\Modules\Permissions\Controller\PermissionsController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/permissions', function (RouteCollectorProxyInterface $group) {

            $group->post('/create', [PermissionsController::class, 'create'])
                ->setName("permissions.create");

            $group->get('', [PermissionsController::class, 'list'])
                ->setName("permissions.list");

            $group->get('/{uuid}', [PermissionsController::class, 'getPermission'])
                ->setName("permissions.view")
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}', [PermissionsController::class, 'update'])
                ->setName("permissions.update")
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}', [PermissionsController::class, 'delete'])
                ->setName("permissions.delete")
                ->add(UuidMiddleware::class);

        })
            ->add(AuthorizationMiddleware::class)
            ->add(AuthenticationMiddleware::class)
            ->add(CsrfMiddleware::class);
    }
}
