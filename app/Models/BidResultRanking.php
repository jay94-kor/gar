<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidResultRanking extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'bid_amount' => 'integer',
            'bid_rate' => 'decimal:3',
            'is_winner' => 'boolean',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(BidResult::class, 'bid_result_id');
    }
}
