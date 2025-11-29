<?php
declare(strict_types=1);

namespace App\Models;

class Category extends BaseModel
{
    protected string $table = 'categories';
    protected array $fillable = ['name', 'icon', 'description'];
}
