<?php

use App\Shared\Database\Database;
use App\Shared\Exception\ExceptionMessageResolver;
use App\Shared\Helper;
use App\Shared\Jwt\JwtService;
use App\Middleware\JwtMiddleware;
use App\Middleware\ErrorMiddleware;
use App\Modules\Auth\Repository\AuthRepository;
use App\Modules\Auth\Service\AuthService;
use App\Modules\Users\Repository\UserRepository;
use App\Modules\Users\Service\UserService;
use App\Modules\Roles\Service\RolesService;
use App\Modules\Roles\Repository\RolesRepository;
use App\Modules\Permissions\Service\PermissionsService;
use App\Modules\Permissions\Repository\PermissionsRepository;

return function ($container) {


    $container->set(ExceptionMessageResolver::class, function () {
        $locale = $_ENV['APP_LOCALE'] ?? 'en';

        return new ExceptionMessageResolver(
            __DIR__ . '/../Shared/Exception',
            $locale
        );
    });

    $container->set(ErrorMiddleware::class, function ($container) {
        return new ErrorMiddleware(
            $container->get(ExceptionMessageResolver::class)
        );
    });

    $container->set(Database::class, function () {
        return new Database(
            ['host' => $_ENV['DB_HOST'] ?? '',
                'user' => $_ENV['DB_USER'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? '',
                'db' => $_ENV['DB_NAME'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? ''
            ]);
    });

    $container->set(UserRepository::class, function ($c) {
        return new UserRepository($c->get(Database::class));
    });

    $container->set(AuthRepository::class, function ($c) {
        return new AuthRepository($c->get(Database::class));
    });

    $container->set(AuthService::class, function ($c) {
        return new AuthService($c->get(UserRepository::class));
    });

    $container->set(UserService::class, function ($c) {
        return new UserService(
            $c->get(UserRepository::class),
            $c->get(Helper::class)
        );
    });

    $container->set(JwtService::class, function ($c) {
        return new JwtService(
            $c->get(AuthRepository::class),
            $c->get(UserRepository::class)
        );
    });

    $container->set(JwtMiddleware::class, function ($c) {
        return new JwtMiddleware(
            $c->get(JwtService::class),
            $c->get(UserService::class)
        );
    });

    $container->set(PermissionsRepository::class, function ($c) {
        return new PermissionsRepository($c->get(Database::class));
    });

    $container->set(PermissionsService::class, function ($c) {
        return new PermissionsService(
            $c->get(PermissionsRepository::class),
            $c->get(Helper::class)
        );
    });

    $container->set(RolesRepository::class, function ($c) {
        return new RolesRepository($c->get(Database::class));
    });

    $container->set(RolesService::class, function ($c) {
        return new RolesService(
            $c->get(RolesRepository::class),
            $c->get(PermissionsRepository::class),
            $c->get(UserRepository::class),
            $c->get(Helper::class)
        );
    });


};
