<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\ChargingPoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ChargingPointController
{
    private ChargingPoint $points;

    public function __construct()
    {
        $this->points = new ChargingPoint(Database::getConnection());
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);

        $result = $this->points->paginate($page, $perPage);
        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $point = $this->points->findById($id);
        if (!$point) {
            return ResponseHelper::error($response, 'Ponto de carga não encontrado.', [], 404);
        }

        return ResponseHelper::success($response, $point);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'user_id' => ['required'],
            'name' => ['required'],
            'latitude' => ['required'],
            'longitude' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->points->create($data);
        $point = $this->points->findById($id);

        return ResponseHelper::json($response, true, $point, 'Ponto de carga criado.', [], [], 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->points->findById($id)) {
            return ResponseHelper::error($response, 'Ponto de carga não encontrado.', [], 404);
        }

        $data = $request->getParsedBody() ?? [];
        $this->points->update($id, $data);
        $point = $this->points->findById($id);

        return ResponseHelper::success($response, $point, 'Ponto de carga atualizado.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->points->findById($id)) {
            return ResponseHelper::error($response, 'Ponto de carga não encontrado.', [], 404);
        }

        $this->points->delete($id);
        return ResponseHelper::success($response, null, 'Ponto de carga removido.');
    }
}
