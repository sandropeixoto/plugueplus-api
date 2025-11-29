<?php
declare(strict_types=1);

namespace App\Models;

class ClassifiedCategory extends BaseModel
{
    protected string $table = 'classified_categories';
    protected array $fillable = ['name', 'slug', 'description', 'icon'];
}
