<?php
declare(strict_types=1);

namespace App\Models;

class PostComment extends BaseModel
{
    protected string $table = 'post_comments';
    protected array $fillable = ['post_id', 'user_id', 'comment'];
}
