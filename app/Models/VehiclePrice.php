<?php

namespace App\Models;

use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fuel_type' => FuelType::class,
            'model_year' => 'integer',
            'new_price' => 'integer',
            'registration_cost' => 'integer',
            'resale_12m' => 'integer',
            'resale_24m' => 'integer',
            'resale_36m' => 'integer',
            'resale_48m' => 'integer',
            'insurance_annual' => 'integer',
        ];
    }
}
