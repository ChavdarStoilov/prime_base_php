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
     * @param UserRepository $userRepository - репозиторито за Users module
     * @param array $requiredPermissions - permissions, нужни за този route
     * @param bool $devFallback - ако няма готови modules, разрешава всички permissions (за dev)
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

        $userUuid = $userPayload['sub'];

        // Намираме user record
        $user = $this->userRepository->findByUUID($userUuid);
        if (!$user) {
            throw new UnauthorizedException('User not found');
        }

        if (!(bool)$user['is_active']) {
            throw new UnauthorizedException('User is inactive');
        }

        // --- Получаване на permissions ---
        // TODO: Replace this with real Roles & Permissions modules
        $userPermissions = $this->getUserPermissions($user['id']);

        // Проверка дали user има всички нужни permissions
        foreach ($this->requiredPermissions as $perm) {
            if (!in_array($perm, $userPermissions, true)) {
                Logger::log("RBAC: user {$userUuid} missing permission: {$perm}");
                throw new UnauthorizedException('Insufficient permissions');
            }
        }

        return $handler->handle($request);
    }

    /**
     * Временен placeholder метод за permissions
     * След това ще се замени с реалните modules
     *
     * @param int $userId
     * @return array
     */
    private function getUserPermissions(int $userId): array
    {
        if ($this->devFallback) {
            // Dev fallback: разрешени всички действия
            return ['*'];
        }

        // TODO: тук се свързва с Roles/Permissions modules
        // Пример:
        // return $this->rolesService->getPermissionsForUser($userId);

        return []; // по default няма permissions
    }
}
