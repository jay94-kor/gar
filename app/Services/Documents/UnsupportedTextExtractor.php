<?php

namespace App\Services\Documents;

use App\Models\BidDocument;
use RuntimeException;

class UnsupportedTextExtractor implements DocumentTextExtractor
{
    public function supports(BidDocument $document): bool
    {
        return true;
    }

    public function extract(BidDocument $document): string
    {
        throw new RuntimeException(sprintf('No text extractor is available for %s.', $document->file_type?->value ?? 'unknown'));
    }
}
