<?php

namespace App\Enums;

enum BidResultStatus: string
{
    case Awarded = 'awarded';
    case Rebid = 'rebid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';
}
