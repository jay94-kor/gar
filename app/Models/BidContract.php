<?php

namespace App\Models;

use App\Enums\FundingImplication;
use App\Enums\VehicleCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidContract extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'vehicle_condition' => VehicleCondition::class,
            'registration_requirement' => 'boolean',
            'funding_implication' => FundingImplication::class,
            'year_threshold' => 'integer',
            'contract_months' => 'integer',
            'prepayment_rate' => 'decimal:2',
            'prepayment_amount' => 'integer',
            'deposit' => 'integer',
            'annual_mileage' => 'integer',
            'residual_value_rate' => 'decimal:2',
            'opening_fee' => 'integer',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}
