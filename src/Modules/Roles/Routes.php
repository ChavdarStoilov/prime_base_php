<?php

namespace App\Modules\Roles;

use App\Middleware\AuthorizationMiddleware;
use App\Middleware\CsrfMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Middleware\AuthenticationMiddleware;
use App\Modules\Roles\Controller\RolesController;
use App\Middleware\UuidMiddleware;

class Routes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/roles', function (RouteCollectorProxyInterface $group) {

            $group->post('/create/', [RolesController::class, 'create']);

            $group->get('/', [RolesController::class, 'list'])
                ->setName("roles.view");

            $group->get('/{uuid}/', [RolesController::class, 'getRole'])
                ->setName("roles.view")
                ->add(UuidMiddleware::class);

            $group->post('/assign/', [RolesController::class, 'attachRolePermission'])
                ->setName("role_permission.assign");

            $group->delete('/detach/', [RolesController::class, 'detachRolePermission'])
                ->setName("role_permission.detach");

            $group->post("/assign_user_role/{uuid}/", [RolesController::class, 'assignRole'])
                ->setName("user_roles.assign")
                ->add(UuidMiddleware::class);

            $group->delete("/detach_user_role/{uuid}/", [RolesController::class, 'detachRole'])
                ->setName("user_roles.detach")
                ->add(UuidMiddleware::class);

            $group->put('/update/{uuid}/', [RolesController::class, 'update'])
                ->setName("roles.update")
                ->add(UuidMiddleware::class);

            $group->delete('/delete/{uuid}/', [RolesController::class, 'delete'])
                ->setName("roles.delete")
                ->add(UuidMiddleware::class);


        })
            ->add(AuthorizationMiddleware::class)
            ->add(AuthenticationMiddleware::class)
            ->add(CsrfMiddleware::class);
    }
}
