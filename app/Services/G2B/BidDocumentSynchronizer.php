<?php

namespace App\Services\G2B;

use App\Enums\DocumentType;
use App\Models\Bid;
use App\Models\BidDocument;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BidDocumentSynchronizer
{
    public function __construct(
        protected BidPublicClient $client,
    ) {
    }

    /**
     * @return Collection<int, BidDocument>
     */
    public function sync(Bid $bid, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        $notice = $this->resolveNotice($bid, $from, $to);
        $attachments = $this->extractAttachments($notice);

        $bid->update([
            'raw_data' => $notice,
            'pipeline_status' => $attachments === [] ? $bid->pipeline_status : 'documents_pending',
        ]);

        $documents = collect();

        foreach ($attachments as $attachment) {
            $documents->push(BidDocument::query()->updateOrCreate(
                [
                    'bid_id' => $bid->id,
                    'seq' => $attachment['seq'],
                ],
                [
                    'filename' => $attachment['filename'],
                    'url' => $attachment['url'],
                    'file_type' => $attachment['file_type'],
                    'status' => $attachment['status'],
                ],
            ));
        }

        return $documents;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveNotice(Bid $bid, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        if (is_array($bid->raw_data) && ($bid->raw_data['bidNtceNo'] ?? null) === $bid->bid_ntce_no) {
            return $bid->raw_data;
        }

        $window = $this->resolveWindow($bid, $from, $to);

        return $this->client->findServiceBidNotice(
            bidNtceNo: $bid->bid_ntce_no,
            bidNtceOrd: $bid->bid_ntce_ord,
            from: $window['from'],
            to: $window['to'],
            title: $bid->title,
        );
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    protected function resolveWindow(Bid $bid, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        if ($from !== null && $to !== null) {
            return [
                'from' => CarbonImmutable::parse($from),
                'to' => CarbonImmutable::parse($to),
            ];
        }

        $reference = $bid->bid_close_dt ?? $bid->bid_open_dt ?? now();
        $fromDate = CarbonImmutable::parse($reference)
            ->subDays((int) config('g2b.bid_public.query.lookup_backtrack_days', 45))
            ->startOfDay();
        $toDate = CarbonImmutable::parse($reference)
            ->addDays((int) config('g2b.bid_public.query.lookup_forward_days', 7))
            ->endOfDay();
        $now = CarbonImmutable::now()->endOfDay();

        return [
            'from' => $fromDate,
            'to' => $toDate->greaterThan($now) ? $now : $toDate,
        ];
    }

    /**
     * @param  array<string, mixed>  $notice
     * @return array<int, array{seq:int, filename:string, url:string, file_type:string, status:string}>
     */
    protected function extractAttachments(array $notice): array
    {
        $attachments = [];
        $seenUrls = [];

        for ($index = 1; $index <= 10; $index++) {
            $url = $this->nullableString($notice["ntceSpecDocUrl{$index}"] ?? null);
            $filename = $this->nullableString($notice["ntceSpecFileNm{$index}"] ?? null);

            if ($url === null || isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;
            $attachments[] = [
                'seq' => count($attachments) + 1,
                'filename' => $filename ?? sprintf('attachment-%d%s', $index, $this->detectExtension($url)),
                'url' => $url,
                'file_type' => $this->detectDocumentType($filename, $url)->value,
                'status' => 'queued',
            ];
        }

        $stdUrl = $this->nullableString($notice['stdNtceDocUrl'] ?? null);

        if ($stdUrl !== null && ! isset($seenUrls[$stdUrl])) {
            $attachments[] = [
                'seq' => count($attachments) + 1,
                'filename' => 'standard-notice'.$this->detectExtension($stdUrl),
                'url' => $stdUrl,
                'file_type' => $this->detectDocumentType(null, $stdUrl)->value,
                'status' => 'queued',
            ];
        }

        return $attachments;
    }

    protected function detectDocumentType(?string $filename, string $url): DocumentType
    {
        $extension = strtolower(pathinfo($filename ?: parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return match ($extension) {
            'hwp' => DocumentType::Hwp,
            'hwpx' => DocumentType::Hwpx,
            'pdf' => DocumentType::Pdf,
            default => DocumentType::Etc,
        };
    }

    protected function detectExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? '.'.$extension : '';
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
