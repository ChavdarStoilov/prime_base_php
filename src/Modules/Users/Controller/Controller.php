<?php

namespace App\Modules\Users\Controller;

use App\Modules\Users\Service\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Shared\Logger\Logger;

class Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function list(Request $request, Response $response): Response
    {
        Logger::log("Listing all users");

        $users = $this->userService->listUsers();

        $response->getBody()->write(json_encode($users));

        return $response->withStatus(200);
    }

    public function getUser(Request $request, Response $response, array $args): Response
    {
        $user = $this->userService->getUser($args['uuid']);

        $response->getBody()->write(json_encode($user));

        return $response->withStatus(200);

    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        Logger::log("Provided date for create user: ", $data);

        if (empty($data)) {

            $response->getBody()->write(json_encode([
                'error' => 'Invalid data'
            ]));

            return $response->withStatus(422);

        }

        Logger::log("Start create user");

        $user = $this->userService->createUser($data);

        $response->getBody()->write(json_encode([
            'uuid' => $user->getUuid(),
            'username' => $user->getUsername(),
        ]));

        return $response->withStatus(201);

    }

    public function update(Request $request, Response $response, array $args): Response
    {

        $data = $request->getParsedBody();
        $uuid = $args['uuid'];
        Logger::log("Provided date for update user {$uuid}: ", $data);

        if (empty($data)) {

            $response->getBody()->write(json_encode([
                'error' => 'Invalid data'
            ]));

            return $response->withStatus(422);
        }

        Logger::log("Start update user");

        $updatedUser = $this->userService->updateUserByUuid($uuid, $data);


        $response->getBody()->write(json_encode([
            "message" => "User was successfully updated",
            "data" => $updatedUser
        ]));

        return $response->withStatus(200);


    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $uuid = $args['uuid'];
        Logger::log("Deleting user with uuid: ", $uuid);

        $this->userService->deleteUserByUuid($uuid);

        $response->getBody()->write(json_encode([
            'message' => 'User deleted successfully'
        ]));

        return $response->withStatus(200);
    }

}
