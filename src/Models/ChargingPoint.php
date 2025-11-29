<?php
declare(strict_types=1);

namespace App\Models;

class ChargingPoint extends BaseModel
{
    protected string $table = 'charging_points';
    protected array $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'state',
        'latitude',
        'longitude',
        'connector_type',
        'power_kw',
        'cost_per_kwh',
        'availability',
        'opening_hours',
        'amenities',
    ];
}
