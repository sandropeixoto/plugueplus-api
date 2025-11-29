<?php
declare(strict_types=1);

namespace App\Models;

class ClassifiedImage extends BaseModel
{
    protected string $table = 'classified_images';
    protected array $fillable = ['classified_id', 'image_path', 'is_main'];
}
