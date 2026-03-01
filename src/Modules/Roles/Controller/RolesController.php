<?php

namespace App\Modules\Roles\Controller;

use App\Modules\Roles\Service\RolesService;
use App\Shared\Helper;
use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RolesController
{
    private RolesService $service;
    private Helper $helper;

    public function __construct(
        RolesService $service,
        Helper       $helper
    )
    {
        $this->service = $service;
        $this->helper = $helper;
    }

    /**
     * @throws JsonException
     */
    public function list(Request $request, Response $response): Response
    {
        $roles = $this->service->getAll();

        return $this->helper->json($response, $roles);

    }

    /**
     * @throws JsonException
     */
    public function getRole(Request $request, Response $response, array $args): Response
    {
        $role = $this->service->getByUuid($args['uuid']);

        return $this->helper->json($response, $role);
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

        $creatorUserId = $this->helper->getCurrentUserID($request);

        $role = $this->service->create($data, $creatorUserId);

        return $this->helper->json($response, $role, 201);
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

        $updaterUserId = $this->helper->getCurrentUserID($request);

        $role = $this->service->update($args['uuid'], $data, $updaterUserId);

        return $this->helper->json($response, $role);
    }

    /**
     * @throws JsonException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->service->delete($args['uuid']);

        return $this->helper->json($response, ['message' => 'Role deleted successfully']);
    }


    public function attachRolePermission(Request $request, Response $response, array $args): Response {

        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ['error' => 'Invalid data'], 422);
        }

        $this->service->assignRolePermissions($data['role_uuid'], $data['permissions_uuids']);

        return $this->helper->json($response, ["message" => "Role permissions added successfully"], 201);
    }

    public function detachRolePermission(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ['error' => 'Invalid data'], 422);
        }

        $this->service->deAssignRolePermissions($data['role_uuid'], $data['permissions_uuids']);

        return $this->helper->json($response, ["message" => "Role permissions remove successfully"], 201);

    }

    public function assignRole(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ['error' => 'Invalid data'], 422);
        }

        $this->service->assignRoleToUser($args['uuid'], $data['role_uuids']);

        return $this->helper->json($response, ["message" => "Role was added successfully"], 201);

    }


}
