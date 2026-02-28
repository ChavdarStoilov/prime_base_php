<?php

namespace App\Modules\Users\Controller;

use App\Modules\Users\Service\UserService;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\ValidationException;
use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Shared\Helper;

class UsersController
{
    private UserService $userService;
    private Helper $helper;

    public function __construct(
        UserService $userService,
        Helper $helper
    )
    {
        $this->userService = $userService;
        $this->helper  = $helper;

    }

    /**
     * @throws JsonException
     */
    public function list(Request $request, Response $response): Response
    {
        $users = $this->userService->listUsers();
        return $this->helper->json($response, $users);
    }

    /**
     * @throws JsonException
     */
    public function getUser(Request $request, Response $response, array $args): Response
    {
        $user = $this->userService->getUser($args['uuid']);

        return $this->helper->json($response, $user);
    }

    /**
     * @throws JsonException
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];


        if (empty($data)) {
            return $this->helper->json($response, ['error' => 'Invalid data'], 422);
        }

        if (!isset($data['username']) && !isset($data['password'])) {
            throw new ValidationException(ErrorCodes::USERNAME_AND_PASSWORD_REQUIRED);
        }

        $user = $this->userService->createUser($data);

        return $this->helper->json($response, $user , 201);

    }

    /**
     * @throws JsonException
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ['error' => 'Invalid data'], 422);
        }

        $id = $args['uuid'];

        $updatedUser = $this->userService->updateUserByUuid($id, $data);

        return $this->helper->json($response, $updatedUser);

    }

    /**
     * @throws JsonException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {

        $id = $args['uuid'];
        $this->userService->deleteUserByUuid($id);

        return $this->helper->json($response, ['message' => 'User deleted successfully']);

    }
}
