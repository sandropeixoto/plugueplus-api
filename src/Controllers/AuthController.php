<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    private User $users;
    private string $jwtSecret;
    private int $jwtTtl;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->users = new User($db);
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'change-me';
        $this->jwtTtl = (int) ($_ENV['JWT_TTL'] ?? 86400);
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];

        $errors = ValidationHelper::validate($data, [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        if ($this->users->findByEmail($data['email'])) {
            return ResponseHelper::error($response, 'Email já cadastrado.', ['email' => ['Email já existe.']], 409);
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['user_type'] = $data['user_type'] ?? 'owner';

        $userId = $this->users->create($data);
        $user = $this->users->findById($userId);
        unset($user['password']);

        return ResponseHelper::json($response, true, $user, 'Usuário registrado com sucesso.', [], [], 201);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];

        $errors = ValidationHelper::validate($data, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $user = $this->users->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return ResponseHelper::error($response, 'Credenciais inválidas.', ['email' => ['Usuário ou senha incorretos.']], 401);
        }

        $now = time();
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'iat' => $now,
            'exp' => $now + $this->jwtTtl,
        ];

        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        unset($user['password']);

        return ResponseHelper::success($response, ['token' => $token, 'user' => $user], 'Login realizado.');
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userData = $request->getAttribute('user');
        if (!$userData) {
            return ResponseHelper::error($response, 'Não autenticado.', [], 401);
        }

        return ResponseHelper::success($response, $userData, 'Usuário autenticado.');
    }
}
