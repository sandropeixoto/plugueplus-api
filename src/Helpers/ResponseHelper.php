<?php
declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface;

class ResponseHelper
{
    public static function json(
        ResponseInterface $response,
        bool $success,
        mixed $data = null,
        ?string $message = null,
        array $errors = [],
        array $meta = [],
        int $status = 200
    ): ResponseInterface {
        $payload = [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
            'meta' => $meta,
        ];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function success(
        ResponseInterface $response,
        mixed $data = null,
        ?string $message = null,
        array $meta = []
    ): ResponseInterface {
        return self::json($response, true, $data, $message, [], $meta, 200);
    }

    public static function error(
        ResponseInterface $response,
        ?string $message = null,
        array $errors = [],
        int $status = 400
    ): ResponseInterface {
        return self::json($response, false, null, $message, $errors, [], $status);
    }
}
