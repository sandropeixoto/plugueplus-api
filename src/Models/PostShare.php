<?php
declare(strict_types=1);

namespace App\Models;

class PostShare extends BaseModel
{
    protected string $table = 'post_shares';
    protected array $fillable = ['post_id', 'shared_by_user_id', 'share_url', 'views_count'];
}
