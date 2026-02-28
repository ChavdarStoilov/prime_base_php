<?php

namespace App\Modules\Permissions;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Permissions\Controller\PermissionsController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/permissions', function (RouteCollectorProxyInterface $group) {

            $group->post('/create', [PermissionsController::class, 'create']);

            $group->get('/', [PermissionsController::class, 'list']);

            $group->get('/{uuid}', [PermissionsController::class, 'getPermission'])
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}', [PermissionsController::class, 'update'])
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}', [PermissionsController::class, 'delete'])
                ->add(UuidMiddleware::class);

        })->add(JwtMiddleware::class);
    }
}
