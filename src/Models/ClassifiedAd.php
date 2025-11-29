<?php
declare(strict_types=1);

namespace App\Models;

class ClassifiedAd extends BaseModel
{
    protected string $table = 'classified_ads';
    protected array $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'price',
        'status',
        'views',
        'expires_at',
    ];
}
