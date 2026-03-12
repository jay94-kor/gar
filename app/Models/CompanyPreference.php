<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPreference extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'regions' => 'array',
            'vehicle_types' => 'array',
            'budget_min' => 'integer',
            'budget_max' => 'integer',
            'contract_months_min' => 'integer',
            'contract_months_max' => 'integer',
            'notification_channels' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
