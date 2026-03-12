<?php

namespace App\Enums;

enum BidStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Awarded = 'awarded';
    case Cancelled = 'cancelled';
}
