<?php

namespace App\Services\Documents;

use App\Models\BidDocument;
use RuntimeException;

class BidDocumentTextSynchronizer
{
    public function __construct(
        protected DocumentParserFactory $factory,
    ) {
    }

    public function extract(BidDocument $document, bool $force = false): BidDocument
    {
        if (! $force && $document->extracted_text !== null && $document->status === 'parsed') {
            return $document;
        }

        if ($document->file_path === null) {
            throw new RuntimeException('Document file path is missing.');
        }

        $document->update([
            'parse_attempts' => $document->parse_attempts + 1,
        ]);

        $extractor = $this->factory->for($document);

        try {
            $text = trim($extractor->extract($document));
        } catch (RuntimeException $exception) {
            $document->update([
                'status' => 'unsupported',
                'parsed_at' => now(),
            ]);

            throw $exception;
        }

        $document->update([
            'extracted_text' => $text !== '' ? $text : null,
            'status' => $text !== '' ? 'parsed' : 'unsupported',
            'parsed_at' => now(),
        ]);

        return $document->fresh();
    }
}
