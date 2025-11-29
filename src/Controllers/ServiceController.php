<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\Service;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServiceController
{
    private Service $services;

    public function __construct()
    {
        $this->services = new Service();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);

        $result = $this->services->findAll($page, $perPage);
        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $service = $this->services->findById($id);
        if (!$service) {
            return ResponseHelper::error($response, 'Serviço não encontrado.', [], 404);
        }

        return ResponseHelper::success($response, $service);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'user_id' => ['required'],
            'category_id' => ['required'],
            'name' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->services->create($data);
        $service = $this->services->findById($id);

        return ResponseHelper::json($response, true, $service, 'Serviço criado.', [], [], 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->services->findById($id)) {
            return ResponseHelper::error($response, 'Serviço não encontrado.', [], 404);
        }

        $data = $request->getParsedBody() ?? [];
        $this->services->update($id, $data);
        $service = $this->services->findById($id);

        return ResponseHelper::success($response, $service, 'Serviço atualizado.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->services->findById($id)) {
            return ResponseHelper::error($response, 'Serviço não encontrado.', [], 404);
        }

        $this->services->delete($id);
        return ResponseHelper::success($response, null, 'Serviço removido.');
    }
}
