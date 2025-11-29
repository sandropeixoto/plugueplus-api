<?php
declare(strict_types=1);

namespace App\Models;

class ClassifiedFavorite extends BaseModel
{
    protected string $table = 'classified_favorites';
    protected array $fillable = ['user_id', 'classified_id'];
}
