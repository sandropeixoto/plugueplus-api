<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\ClassifiedAd;
use App\Models\ClassifiedCategory;
use App\Models\ClassifiedFavorite;
use App\Models\ClassifiedImage;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClassifiedController
{
    private PDO $db;
    private ClassifiedAd $ads;
    private ClassifiedCategory $categories;
    private ClassifiedImage $images;
    private ClassifiedFavorite $favorites;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ads = new ClassifiedAd($this->db);
        $this->categories = new ClassifiedCategory($this->db);
        $this->images = new ClassifiedImage($this->db);
        $this->favorites = new ClassifiedFavorite($this->db);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);

        $result = $this->ads->paginate($page, $perPage);
        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $ad = $this->ads->findById($id);
        if (!$ad) {
            return ResponseHelper::error($response, 'Anúncio não encontrado.', [], 404);
        }

        $imageStmt = $this->db->prepare('SELECT * FROM classified_images WHERE classified_id = :id');
        $imageStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $imageStmt->execute();
        $ad['images'] = $imageStmt->fetchAll();

        return ResponseHelper::success($response, $ad);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'user_id' => ['required'],
            'category_id' => ['required'],
            'title' => ['required'],
            'description' => ['required'],
            'price' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->ads->create($data);
        $ad = $this->ads->findById($id);

        return ResponseHelper::json($response, true, $ad, 'Anúncio criado.', [], [], 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->ads->findById($id)) {
            return ResponseHelper::error($response, 'Anúncio não encontrado.', [], 404);
        }

        $data = $request->getParsedBody() ?? [];
        $this->ads->update($id, $data);
        $ad = $this->ads->findById($id);

        return ResponseHelper::success($response, $ad, 'Anúncio atualizado.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->ads->findById($id)) {
            return ResponseHelper::error($response, 'Anúncio não encontrado.', [], 404);
        }

        $this->ads->delete($id);
        return ResponseHelper::success($response, null, 'Anúncio removido.');
    }

    public function favorite(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $classifiedId = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return ResponseHelper::error($response, 'user_id é obrigatório.', ['user_id' => ['Obrigatório']], 422);
        }

        try {
            $this->favorites->create([
                'user_id' => $userId,
                'classified_id' => $classifiedId,
            ]);
        } catch (PDOException $exception) {
            return ResponseHelper::error($response, 'Não foi possível favoritar.', ['favorite' => [$exception->getMessage()]], 400);
        }

        return ResponseHelper::success($response, null, 'Favorito registrado.');
    }

    public function unfavorite(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $classifiedId = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return ResponseHelper::error($response, 'user_id é obrigatório.', ['user_id' => ['Obrigatório']], 422);
        }

        $stmt = $this->db->prepare('DELETE FROM classified_favorites WHERE user_id = :user_id AND classified_id = :classified_id');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':classified_id', $classifiedId, PDO::PARAM_INT);
        $stmt->execute();

        return ResponseHelper::success($response, null, 'Favorito removido.');
    }
}
