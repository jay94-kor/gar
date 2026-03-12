<?php

namespace App\Enums;

enum VehicleCondition: string
{
    case NewOnly = 'new_only';
    case UsedOk = 'used_ok';
    case Unspecified = 'unspecified';
}
