<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected array $fillable = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * Alias para retornar apenas os registros paginados.
     */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        $result = $this->paginate($page, $perPage);
        return $result['data'];
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            throw new PDOException('Nenhum campo válido para inserir.');
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return false;
        }

        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = :{$column}";
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->table,
            implode(', ', $setParts)
        );

        $stmt = $this->db->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    protected function filterFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
