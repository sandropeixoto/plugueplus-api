<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PostController
{
    private PDO $db;
    private Post $posts;
    private PostLike $likes;
    private PostComment $comments;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->posts = new Post($this->db);
        $this->likes = new PostLike($this->db);
        $this->comments = new PostComment($this->db);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);

        $result = $this->posts->paginate($page, $perPage);
        return ResponseHelper::success($response, $result['data'], null, $result['meta']);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $post = $this->posts->findById($id);
        if (!$post) {
            return ResponseHelper::error($response, 'Post não encontrado.', [], 404);
        }

        return ResponseHelper::success($response, $post);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $errors = ValidationHelper::validate($data, [
            'user_id' => ['required'],
            'content' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->posts->create($data);
        $post = $this->posts->findById($id);

        return ResponseHelper::json($response, true, $post, 'Post criado.', [], [], 201);
    }

    public function like(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $postId = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return ResponseHelper::error($response, 'user_id é obrigatório.', ['user_id' => ['Obrigatório']], 422);
        }

        try {
            $this->likes->create(['user_id' => $userId, 'post_id' => $postId]);
        } catch (PDOException $exception) {
            return ResponseHelper::error($response, 'Não foi possível curtir.', ['like' => [$exception->getMessage()]], 400);
        }

        return ResponseHelper::success($response, null, 'Curtida registrada.');
    }

    public function unlike(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $postId = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return ResponseHelper::error($response, 'user_id é obrigatório.', ['user_id' => ['Obrigatório']], 422);
        }

        $stmt = $this->db->prepare('DELETE FROM post_likes WHERE user_id = :user_id AND post_id = :post_id');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();

        return ResponseHelper::success($response, null, 'Curtida removida.');
    }

    public function comment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $postId = (int) ($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];
        $data['post_id'] = $postId;

        $errors = ValidationHelper::validate($data, [
            'post_id' => ['required'],
            'user_id' => ['required'],
            'comment' => ['required'],
        ]);

        if (!empty($errors)) {
            return ResponseHelper::error($response, 'Dados inválidos.', $errors, 422);
        }

        $id = $this->comments->create($data);
        $comment = $this->comments->findById($id);

        return ResponseHelper::json($response, true, $comment, 'Comentário criado.', [], [], 201);
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if (!$this->posts->findById($id)) {
            return ResponseHelper::error($response, 'Post não encontrado.', [], 404);
        }

        $this->posts->delete($id);
        return ResponseHelper::success($response, null, 'Post removido.');
    }

    public function comments(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $postId = (int) ($args['id'] ?? 0);
        $query = $request->getQueryParams();
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM post_comments WHERE post_id = :post_id');
        $countStmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare('SELECT * FROM post_comments WHERE post_id = :post_id LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
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
}
