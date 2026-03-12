<?php

namespace App\Enums;

enum FundingImplication: string
{
    case PurchaseRequired = 'purchase_required';
    case StockEligible = 'stock_eligible';
    case Unknown = 'unknown';
}
