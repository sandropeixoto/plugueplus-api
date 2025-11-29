<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User(Database::getConnection());
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);

        $result = $this->users->paginate($page, $perPage);
        foreach ($result['data'] as &$user) {
            unset($user['password']);
        }

        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $user = $this->users->findById($id);
        if (!$user) {
            return ResponseHelper::error($response, 'Usuário não encontrado.', [], 404);
        }
        unset($user['password']);

        return ResponseHelper::success($response, $user);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];

        $errors = ValidationHelper::validate($data, [
            'email' => ['email'],
        ]);
        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        if (!$this->users->findById($id)) {
            return ResponseHelper::error($response, 'Usuário não encontrado.', [], 404);
        }

        if (isset($data['password'])) {
            $data['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        $this->users->update($id, $data);
        $user = $this->users->findById($id);
        unset($user['password']);

        return ResponseHelper::success($response, $user, 'Usuário atualizado.');
    }
}
