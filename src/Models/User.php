<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'phone',
        'city',
        'state',
        'vehicle_model',
    ];

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
