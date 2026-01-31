<?php
namespace App\Middleware;

use App\Shared\Jwt\JwtService;
use Slim\Psr7\Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use App\Shared\Logger\Logger;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

final class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtService $jwt) {}

    public function process(Request $request, Handler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return $this->unauthorized('Missing token');
        }

        try {
            $payload = $this->jwt->validate($matches[1]);

            return $handler->handle(
                $request->withAttribute('user', $payload)
            );
        } catch (ExpiredException | BeforeValidException $e) {
            Logger::log("JWT error: ", $e->getMessage());
            return $this->unauthorized('Token expired');
        } catch (SignatureInvalidException $error) {
            Logger::log("JWT error: ", $error->getMessage());

            return $this->unauthorized('Invalid token');
        }
    }

    private function unauthorized(string $msg): Response
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => $msg]));
        return $res->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
