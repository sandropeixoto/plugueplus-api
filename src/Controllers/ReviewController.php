<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\Review;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReviewController
{
    private Review $reviews;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->reviews = new Review($this->db);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);
        $pointId = $query['point_id'] ?? null;
        $serviceId = $query['service_id'] ?? null;

        $conditions = [];
        $params = [];

        if ($pointId !== null) {
            $conditions[] = 'point_id = :point_id';
            $params['point_id'] = $pointId;
        }
        if ($serviceId !== null) {
            $conditions[] = 'service_id = :service_id';
            $params['service_id'] = $serviceId;
        }

        $where = '';
        if ($conditions) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reviews {$where}");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT * FROM reviews {$where} LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

        $meta = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
        ];

        return ResponseHelper::success($response, $data, null, $meta);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'user_id' => ['required'],
            'rating' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->reviews->create($data);
        $review = $this->reviews->findById($id);

        return ResponseHelper::json($response, true, $review, 'Avaliação criada.', [], [], 201);
    }
}
