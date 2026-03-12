<?php

namespace App\Services\G2B;

use App\Enums\DocumentType;
use App\Models\BidDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class G2BDocumentDownloader
{
    public function download(BidDocument $document, bool $force = false): BidDocument
    {
        if (! $force && $document->file_path !== null && in_array($document->status, ['downloaded', 'parse_pending', 'parsed'], true)) {
            return $document;
        }

        $document->update([
            'status' => 'downloading',
            'download_attempts' => $document->download_attempts + 1,
            'download_error' => null,
        ]);

        $response = Http::withHeaders([
            'Referer' => (string) config('g2b.documents.download_referer'),
            'User-Agent' => (string) config('g2b.documents.user_agent'),
        ])
            ->connectTimeout((int) config('g2b.bid_result.connect_timeout', 5))
            ->timeout((int) config('g2b.bid_result.timeout', 10))
            ->retry(config('g2b.bid_result.retry_sleep_ms', [200, 500, 1000]), throw: false)
            ->get($document->url);

        if ($response->failed()) {
            $document->update([
                'status' => 'failed',
                'download_error' => sprintf('Download failed with status %s', $response->status()),
            ]);

            throw new RuntimeException(sprintf('Failed to download %s: %s', $document->filename, $response->status()));
        }

        $body = $response->body();
        $disk = (string) config('g2b.documents.disk', 'local');
        $path = $this->buildStoragePath($document);

        Storage::disk($disk)->put($path, $body);

        $document->update([
            'file_path' => $path,
            'content_type' => $response->header('Content-Type'),
            'file_size' => strlen($body),
            'status' => $this->shouldParse($document) ? 'parse_pending' : 'downloaded',
            'downloaded_at' => now(),
        ]);

        return $document->fresh();
    }

    protected function buildStoragePath(BidDocument $document): string
    {
        $basePath = trim((string) config('g2b.documents.path', 'bid-documents'), '/');
        $filename = Str::of($document->filename)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-')
            ->value();

        $filename = $filename !== '' ? $filename : sprintf('document-%d%s', $document->seq, $this->defaultExtension($document));

        return sprintf('%s/%d/%02d-%s', $basePath, $document->bid_id, $document->seq, $filename);
    }

    protected function shouldParse(BidDocument $document): bool
    {
        return in_array($document->file_type, [DocumentType::Pdf, DocumentType::Hwpx], true);
    }

    protected function defaultExtension(BidDocument $document): string
    {
        return match ($document->file_type) {
            DocumentType::Pdf => '.pdf',
            DocumentType::Hwpx => '.hwpx',
            DocumentType::Hwp => '.hwp',
            default => '',
        };
    }
}
