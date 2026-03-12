<?php

namespace App\Services\Documents;

use App\Models\BidDocument;

interface DocumentTextExtractor
{
    public function supports(BidDocument $document): bool;

    public function extract(BidDocument $document): string;
}
