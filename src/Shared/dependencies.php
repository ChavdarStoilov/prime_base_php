<?php

use App\Shared\Database\Database;
use App\Shared\Jwt\JwtService;
use App\Middleware\JwtMiddleware;
use App\Modules\Auth\Repository\AuthRepository;
use App\Modules\Auth\Service\AuthService;
use App\Modules\Users\Repository\UserRepository;
use App\Modules\Users\Service\UserService;

return function ($container) {

//    $container->set(AuthRepository::class, function($c){
//        return new AuthRepository($c->get(Database::class));
//    });
//
//    $container->set(UserRepository::class, function($c){
//        return new UserRepository($c->get(Database::class));
//    });

    $container->set(AuthService::class, function($c){
        return new AuthService($c->get(UserRepository::class));
    });

    $container->set(UserService::class, function($c){
        return new UserService($c->get(UserRepository::class));
    });

    $container->set(JwtService::class, function($c){
        return new JwtService($c->get(AuthRepository::class));
    });

    $container->set(JwtMiddleware::class, function($c){
        return new JwtMiddleware($c->get(JwtService::class));
    });
};
