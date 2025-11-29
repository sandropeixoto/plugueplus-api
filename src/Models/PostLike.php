<?php
declare(strict_types=1);

namespace App\Models;

class PostLike extends BaseModel
{
    protected string $table = 'post_likes';
    protected array $fillable = ['user_id', 'post_id'];
}
