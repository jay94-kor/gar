<?php

namespace App\Models;

use App\Enums\CompanySize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'branches' => 'array',
            'fleet' => 'array',
            'company_size' => CompanySize::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(CompanyPreference::class);
    }

    public function fleets(): HasMany
    {
        return $this->hasMany(CompanyFleet::class);
    }

    public function credential(): HasOne
    {
        return $this->hasOne(CompanyCredential::class);
    }

    public function simulationResults(): HasMany
    {
        return $this->hasMany(SimulationResult::class);
    }
}
