<?php

namespace App\Modules\Roles;

use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\JwtMiddleware;
use App\Modules\Roles\Controller\RolesController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/roles', function (RouteCollectorProxyInterface $group) {

            $group->post('/create', [RolesController::class, 'create']);

            $group->get('/', [RolesController::class, 'list']);

            $group->get('/{uuid}', [RolesController::class, 'getRole'])
                ->add(UuidMiddleware::class);

            $group->post('/assign', [RolesController::class, 'attachRolePermission']);

            $group->delete('/detach', [RolesController::class, 'detachRolePermission']);

            $group->post("/assign_user_role/{uuid}", [RolesController::class, 'assignRole'])
            ->add(UuidMiddleware::class);

            $group->delete("/detach_user_role/{uuid}", [RolesController::class, 'detachRole'])
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}', [RolesController::class, 'update'])
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}', [RolesController::class, 'delete'])
                ->add(UuidMiddleware::class);


        })->add(JwtMiddleware::class);
    }
}
