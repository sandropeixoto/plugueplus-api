<?php
declare(strict_types=1);

namespace App\Models;

class PostShareView extends BaseModel
{
    protected string $table = 'post_share_views';
    protected array $fillable = ['share_id', 'viewed_by_user_id', 'ip_address', 'user_agent'];
}
