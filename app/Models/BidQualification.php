<?php

namespace App\Models;

use App\Enums\CompanySize;
use App\Enums\EvaluationMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidQualification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'company_size_limit' => CompanySize::class,
            'joint_contract' => 'boolean',
            'subcontract' => 'boolean',
            'other_requirements' => 'array',
            'evaluation_method' => EvaluationMethod::class,
            'success_threshold' => 'decimal:2',
            'passing_score' => 'integer',
            'preliminary_prices_count' => 'integer',
            'score_performance' => 'integer',
            'score_financial' => 'integer',
            'score_afterservice' => 'integer',
            'score_price' => 'integer',
            'score_credibility_plus' => 'decimal:2',
            'score_credibility_minus' => 'decimal:2',
            'score_disqualify' => 'integer',
            'score_adjusted' => 'boolean',
            'required_docs' => 'array',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}
