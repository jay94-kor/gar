<?php

namespace App\Services\Documents;

use App\Enums\DocumentType;
use App\Models\BidDocument;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class HwpxTextExtractor implements DocumentTextExtractor
{
    public function supports(BidDocument $document): bool
    {
        return $document->file_type === DocumentType::Hwpx;
    }

    public function extract(BidDocument $document): string
    {
        $path = Storage::disk((string) config('g2b.documents.disk', 'local'))->path((string) $document->file_path);
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open HWPX document.');
        }

        $chunks = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || ! str_starts_with($name, 'Contents/') || ! str_ends_with($name, '.xml')) {
                continue;
            }

            $content = $zip->getFromIndex($index);

            if (! is_string($content) || trim($content) === '') {
                continue;
            }

            $chunks[] = $this->normalizeXmlText($content);
        }

        $zip->close();

        return trim(implode("\n\n", array_filter($chunks)));
    }

    protected function normalizeXmlText(string $xml): string
    {
        $text = preg_replace('/<[^>]+>/', ' ', $xml) ?? $xml;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
