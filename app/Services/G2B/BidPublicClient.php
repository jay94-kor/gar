<?php

namespace App\Services\G2B;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BidPublicClient
{
    /**
     * Find a single service bid notice by bid notice number and order.
     *
     * @return array<string, mixed>
     */
    public function findServiceBidNotice(
        string $bidNtceNo,
        string $bidNtceOrd,
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $title = null,
    ): array {
        $queries = [];

        if ($title !== null && trim($title) !== '') {
            $queries[] = ['bidNtceNm' => $title];
        }

        $queries[] = [];

        foreach ($queries as $extraQuery) {
            foreach ($this->chunkWindow($from, $to) as $window) {
                $notice = $this->searchServiceBidNotice(
                    bidNtceNo: $bidNtceNo,
                    bidNtceOrd: $bidNtceOrd,
                    from: $window['from'],
                    to: $window['to'],
                    extraQuery: $extraQuery,
                );

                if ($notice !== null) {
                    return $notice;
                }
            }
        }

        throw new RuntimeException(sprintf('Unable to locate G2B bid notice %s/%s within the search window.', $bidNtceNo, $bidNtceOrd));
    }

    /**
     * @param  array<string, mixed>  $extraQuery
     * @return array<string, mixed>|null
     */
    protected function searchServiceBidNotice(
        string $bidNtceNo,
        string $bidNtceOrd,
        CarbonInterface $from,
        CarbonInterface $to,
        array $extraQuery = [],
    ): ?array {
        $serviceKey = (string) config('g2b.bid_public.service_key');

        if ($serviceKey === '') {
            throw new RuntimeException('G2B bid public service key is not configured.');
        }

        $page = 1;
        $maxPages = max(1, (int) config('g2b.bid_public.query.max_pages', 20));

        do {
            $payload = $this->requestServiceBidNotices($from, $to, $page, $extraQuery);
            $items = $this->normalizeItems(data_get($payload, 'response.body.items'));

            foreach ($items as $item) {
                if (($item['bidNtceNo'] ?? null) === $bidNtceNo && ($item['bidNtceOrd'] ?? null) === $bidNtceOrd) {
                    return $item;
                }
            }

            $page++;
            $totalCount = (int) data_get($payload, 'response.body.totalCount', count($items));
            $rows = max(1, (int) config('g2b.bid_public.query.default_rows', 100));
        } while ($items !== [] && $page <= $maxPages && (($page - 1) * $rows) < $totalCount);

        return null;
    }

    /**
     * @return array<int, array{from: CarbonImmutable, to: CarbonImmutable}>
     */
    protected function chunkWindow(CarbonInterface $from, CarbonInterface $to): array
    {
        $maxWindowDays = max(1, (int) config('g2b.bid_public.query.max_window_days', 30));
        $windows = [];
        $cursor = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->endOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $windowEnd = $cursor->addDays($maxWindowDays - 1)->endOfDay();

            if ($windowEnd->greaterThan($end)) {
                $windowEnd = $end;
            }

            $windows[] = [
                'from' => $cursor,
                'to' => $windowEnd,
            ];

            $cursor = $windowEnd->addSecond()->startOfDay();
        }

        return $windows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestServiceBidNotices(CarbonInterface $from, CarbonInterface $to, int $page, array $extraQuery = []): array
    {
        $query = array_merge([
            'serviceKey' => (string) config('g2b.bid_public.service_key'),
            'type' => 'json',
            'inqryDiv' => (string) config('g2b.bid_public.query.inquiry_division', '1'),
            'inqryBgnDt' => CarbonImmutable::parse($from)->format('Ymd'),
            'inqryEndDt' => CarbonImmutable::parse($to)->format('Ymd'),
            'numOfRows' => (int) config('g2b.bid_public.query.default_rows', 100),
            'pageNo' => $page,
        ], array_filter($extraQuery, fn (mixed $value): bool => $value !== null && $value !== ''));

        $response = Http::baseUrl(rtrim((string) config('g2b.bid_public.base_url'), '/'))
            ->acceptJson()
            ->connectTimeout((int) config('g2b.bid_result.connect_timeout', 5))
            ->timeout((int) config('g2b.bid_result.timeout', 10))
            ->retry(config('g2b.bid_result.retry_sleep_ms', [200, 500, 1000]), throw: false)
            ->get((string) config('g2b.bid_public.endpoints.service'), $query);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'G2B bid public request failed with status %s: %s',
                $response->status(),
                str($response->body())->limit(200)->toString(),
            ));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Unexpected G2B bid public response format.');
        }

        $resultCode = (string) data_get($payload, 'response.header.resultCode', '');

        if ($resultCode !== '' && $resultCode !== '00') {
            throw new RuntimeException((string) data_get($payload, 'response.header.resultMsg', 'G2B bid public request failed.'));
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            return [];
        }

        if (isset($items['item'])) {
            $items = $items['item'];
        }

        if (Arr::isList($items)) {
            return array_values(array_filter($items, 'is_array'));
        }

        return is_array($items) ? [$items] : [];
    }
}
