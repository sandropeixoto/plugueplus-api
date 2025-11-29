<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ResponseHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return ResponseHelper::error($response, 'Token não informado.', [], 401);
        }

        $token = substr($authHeader, 7);
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if ($secret === '') {
            return ResponseHelper::error($response, 'JWT_SECRET não configurado.', [], 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable $exception) {
            return ResponseHelper::error($response, 'Token inválido ou expirado.', ['token' => [$exception->getMessage()]], 401);
        }

        $request = $request->withAttribute('user', (array) $decoded);
        return $handler->handle($request);
    }
}
