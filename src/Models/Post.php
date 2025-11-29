<?php
declare(strict_types=1);

namespace App\Models;

class Post extends BaseModel
{
    protected string $table = 'posts';
    protected array $fillable = ['user_id', 'content', 'image_url'];
}
