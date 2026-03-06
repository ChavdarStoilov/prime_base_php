<?php
namespace App\Middleware;

use App\Modules\Users\Service\UserService;
use App\Shared\Jwt\JwtService;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use App\Shared\Logger\Logger;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwt,
        private UserService $userService
    ) {}

    public function process(Request $request, Handler $handler): ResponseInterface
    {
        try {
            $token = $this->extractToken($request);

            if (!$token) {
                return $this->unauthorized('Missing token');
            }

            $payload = $this->jwt->validate($token);

            $user = $this->userService->getUser($payload['sub'], true);

            if (!$user || !$user->isActive()) {
                return $this->unauthorized('User not active');
            }

            $request = $request->withAttribute('current_user', [
                'id'   => $user->getUserId(),
                'uuid' => $user->getUuid(),
            ]);

            return $handler->handle($request);

        } catch (ExpiredException | BeforeValidException) {
            return $this->unauthorized('Token expired');
        } catch (SignatureInvalidException) {
            return $this->unauthorized('Invalid token');
        }
//        catch (\Throwable $e) {
//            Logger::log("Auth fatal: " . $e->getMessage());
//            return $this->unauthorized('Authentication error');
//        }
    }

    private function extractToken(Request $request): ?string
    {
        $cookies = $request->getCookieParams();
        return $cookies['access_token'] ?? null;
    }

    private function unauthorized(string $msg): ResponseInterface
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => $msg], JSON_THROW_ON_ERROR));
        return $res->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
