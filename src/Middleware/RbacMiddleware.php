<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\UnauthorizedException;
use App\Shared\Logger\Logger;

final class RbacMiddleware implements MiddlewareInterface
{
    /**
     * @param UserRepository $userRepository
     * @param array $requiredPermissions
     * @param bool $devFallback
     */
    public function __construct(
        private UserRepository $userRepository,
        private array $requiredPermissions = [],
        private bool $devFallback = true
    ) {}

    public function process(Request $request, Handler $handler): Response
    {
        $userPayload = $request->getAttribute('user');

        if (!$userPayload || !isset($userPayload['sub'])) {
            throw new UnauthorizedException('User not authenticated');
        }

        $userId = $userPayload['sub'];

        $userPermissions = $this->getUserPermissions($userId);

        foreach ($this->requiredPermissions as $perm) {
            if (!in_array($perm, $userPermissions, true)) {
                Logger::log("RBAC: user {$userId} missing permission: {$perm}");
                throw new UnauthorizedException('Insufficient permissions');
            }
        }

        return $handler->handle($request);
    }

    /**
     *
     * @param int $userId
     * @return array
     */
    private function getUserPermissions(int $userId): array
    {
        if ($this->devFallback) {
            return ['*'];
        }

        // TODO: тук се свързва с Roles/Permissions modules
        // Пример:
        // return $this->rolesService->getPermissionsForUser($userId);

        return [];
    }
}
