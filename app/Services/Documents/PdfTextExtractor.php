<?php

namespace App\Services\Documents;

use App\Enums\DocumentType;
use App\Models\BidDocument;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfTextExtractor implements DocumentTextExtractor
{
    public function supports(BidDocument $document): bool
    {
        return $document->file_type === DocumentType::Pdf;
    }

    public function extract(BidDocument $document): string
    {
        $path = Storage::disk((string) config('g2b.documents.disk', 'local'))->path((string) $document->file_path);
        $process = new Process(['pdftotext', '-layout', $path, '-']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput() ?: 'pdftotext failed.');
        }

        return trim($process->getOutput());
    }
}
