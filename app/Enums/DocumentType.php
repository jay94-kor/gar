<?php

namespace App\Enums;

enum DocumentType: string
{
    case Hwp = 'hwp';
    case Hwpx = 'hwpx';
    case Pdf = 'pdf';
    case Etc = 'etc';
}
