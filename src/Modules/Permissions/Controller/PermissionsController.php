<?php

namespace App\Modules\Permissions\Controller;


use App\Shared\Helper;
use App\Modules\Permissions\Service\PermissionsService;
use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionsController
{
    private PermissionsService $service;
    private Helper $helper;

    public function __construct(
        PermissionsService $service,
        Helper             $helper
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
        $permissions = $this->service->getAll();

        return $this->helper->json($response, $permissions);
    }

    /**
     * @throws JsonException
     */
    public function getPermission(Request $request, Response $response, array $args): Response
    {

        $uuid = $args['uuid'];

        $permission = $this->service->getByUuid($uuid);

        return $this->helper->json($response, $permission);
    }

    /**
     * @throws JsonException
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ["message" => "Invalid inputs"], 415);
        }

        $creatorUserId = $this->helper->getCurrentUserID($request);

        $permission = $this->service->create($data, $creatorUserId);


        return $this->helper->json($response, $permission, 201);
    }

    /**
     * @throws JsonException
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            return $this->helper->json($response, ["message" => "Invalid inputs"], 415);
        }

        $updaterUserId = $this->helper->getCurrentUserID($request);

        $uuid = $args['uuid'];

        $permission = $this->service->update($uuid, $data, $updaterUserId);

        return $this->helper->json($response, $permission);
    }


    public function delete(Request $request, Response $response, array $args): Response
    {
        $uuid = $args['uuid'];

        $this->service->delete($uuid);

        return $this->helper->json($response, ['message' => 'User deleted successfully']);

    }


}
