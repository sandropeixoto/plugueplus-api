<?php
declare(strict_types=1);

namespace App\Models;

class Review extends BaseModel
{
    protected string $table = 'reviews';
    protected array $fillable = [
        'user_id',
        'point_id',
        'service_id',
        'rating',
        'comment',
    ];
}
