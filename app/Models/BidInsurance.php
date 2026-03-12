<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidInsurance extends Model
{
    use HasFactory;

    protected $table = 'bid_insurance';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'property_damage' => 'integer',
            'own_vehicle' => 'boolean',
            'own_vehicle_deductible' => 'integer',
            'driver_age_min' => 'integer',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}
