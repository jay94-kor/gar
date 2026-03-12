<?php

namespace App\Models;

use App\Enums\BidResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidResult extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'result_status' => BidResultStatus::class,
            'awarded_amount' => 'integer',
            'award_rate' => 'decimal:3',
            'participant_count' => 'integer',
            'award_dt' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(BidResultRanking::class);
    }
}
