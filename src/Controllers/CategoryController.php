<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\Category;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CategoryController
{
    private Category $categories;

    public function __construct()
    {
        $this->categories = new Category(Database::getConnection());
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);
        $result = $this->categories->paginate($page, $perPage);

        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'name' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->categories->create($data);
        $category = $this->categories->findById($id);

        return ResponseHelper::json($response, true, $category, 'Categoria criada.', [], [], 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->categories->findById($id)) {
            return ResponseHelper::error($response, 'Categoria não encontrada.', [], 404);
        }

        $data = $request->getParsedBody() ?? [];
        $this->categories->update($id, $data);
        $category = $this->categories->findById($id);

        return ResponseHelper::success($response, $category, 'Categoria atualizada.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->categories->findById($id)) {
            return ResponseHelper::error($response, 'Categoria não encontrada.', [], 404);
        }

        $this->categories->delete($id);
        return ResponseHelper::success($response, null, 'Categoria removida.');
    }
}
