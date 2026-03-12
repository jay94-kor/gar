<?php

namespace App\Models;

use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bid extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'budget' => 'integer',
            'bid_open_dt' => 'datetime',
            'bid_close_dt' => 'datetime',
            'raw_data' => 'array',
            'status' => BidStatus::class,
        ];
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(BidAnalysis::class);
    }

    public function currentAnalysis(): HasOne
    {
        return $this->hasOne(BidAnalysis::class)->where('is_current', true);
    }

    public function result(): HasOne
    {
        return $this->hasOne(BidResult::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BidDocument::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(BidVehicle::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(BidContract::class);
    }

    public function insurance(): HasOne
    {
        return $this->hasOne(BidInsurance::class);
    }

    public function qualification(): HasOne
    {
        return $this->hasOne(BidQualification::class);
    }

    public function performance(): HasOne
    {
        return $this->hasOne(BidPerformance::class);
    }

    public function financial(): HasOne
    {
        return $this->hasOne(BidFinancial::class);
    }

    public function credibilities(): HasMany
    {
        return $this->hasMany(BidCredibility::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(BidChecklist::class);
    }

    public function simulationResults(): HasMany
    {
        return $this->hasMany(SimulationResult::class);
    }
}
