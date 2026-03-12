<?php

namespace App\Enums;

enum EvaluationMethod: string
{
    case Competitive = 'competitive';
    case Negotiation = 'negotiation';
    case Estimate = 'estimate';
}
