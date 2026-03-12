<?php

namespace App\Services\Documents;

use App\Models\BidDocument;

class DocumentParserFactory
{
    /**
     * @param  iterable<int, DocumentTextExtractor>  $extractors
     */
    public function __construct(
        protected iterable $extractors = [],
    ) {
        if ($this->extractors === []) {
            $this->extractors = [
                app(PdfTextExtractor::class),
                app(HwpxTextExtractor::class),
                app(UnsupportedTextExtractor::class),
            ];
        }
    }

    public function for(BidDocument $document): DocumentTextExtractor
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($document)) {
                return $extractor;
            }
        }

        return app(UnsupportedTextExtractor::class);
    }
}
