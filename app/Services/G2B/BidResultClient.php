<?php

namespace App\Services\G2B;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BidResultClient
{
    /**
     * Fetch awarded bid results for a single service bid notice.
     *
     * @return array{
     *     request: array<string, mixed>,
     *     total_count: int,
     *     items: array<int, array<string, mixed>>,
     *     raw: array<string, mixed>
     * }
     */
    public function fetchServiceResults(
        string $bidNtceNo,
        string $bidNtceOrd = '000',
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $serviceKey = (string) config('g2b.bid_result.service_key');

        if ($serviceKey === '') {
            throw new RuntimeException('G2B bid result service key is not configured.');
        }

        $query = [
            'serviceKey' => $serviceKey,
            'type' => 'json',
            'bidNtceNo' => $bidNtceNo,
            'bidNtceOrd' => $bidNtceOrd,
            'inqryDiv' => (string) config('g2b.bid_result.inquiry_division', '4'),
            'inqryBgnDt' => $this->formatDate($from ?? now()->subDays((int) config('g2b.bid_result.max_window_days', 120))),
            'inqryEndDt' => $this->formatDate($to ?? now()),
            'pageNo' => 1,
            'numOfRows' => (int) config('g2b.bid_result.default_rows', 20),
        ];

        $response = Http::baseUrl(rtrim((string) config('g2b.bid_result.base_url'), '/'))
            ->acceptJson()
            ->connectTimeout((int) config('g2b.bid_result.connect_timeout', 5))
            ->timeout((int) config('g2b.bid_result.timeout', 10))
            ->retry(config('g2b.bid_result.retry_sleep_ms', [200, 500, 1000]), throw: false)
            ->get((string) config('g2b.bid_result.endpoints.service'), $query);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'G2B bid result request failed with status %s: %s',
                $response->status(),
                str($response->body())->limit(200)->toString(),
            ));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Unexpected G2B bid result response format.');
        }

        $resultCode = (string) data_get($payload, 'response.header.resultCode', '');

        if ($resultCode !== '' && $resultCode !== '00') {
            throw new RuntimeException((string) data_get($payload, 'response.header.resultMsg', 'G2B bid result request failed.'));
        }

        $items = array_map(
            fn (array $item): array => $this->normalizeItem($item),
            $this->normalizeItems(data_get($payload, 'response.body.items.item', data_get($payload, 'response.body.items')))
        );

        return [
            'request' => $query,
            'total_count' => (int) data_get($payload, 'response.body.totalCount', count($items)),
            'items' => $items,
            'raw' => $payload,
        ];
    }

    protected function formatDate(CarbonInterface $date): string
    {
        return CarbonImmutable::parse($date)->format('Ymd');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            return [];
        }

        if (Arr::isList($items)) {
            return array_values(array_filter($items, 'is_array'));
        }

        return [$items];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function normalizeItem(array $item): array
    {
        $winner = $this->nullableString($this->firstFilled($item, [
            'bidwinnrNm',
            'cmpnyNm',
            'prtcptCnm',
            'entrpsNm',
        ]));

        $rank = $this->toInteger($this->firstFilled($item, [
            'opengRank',
            'rank',
            'bidRank',
        ]));

        return [
            'bid_ntce_no' => $this->nullableString(data_get($item, 'bidNtceNo')),
            'bid_ntce_ord' => $this->nullableString(data_get($item, 'bidNtceOrd')) ?? '000',
            'title' => $this->nullableString(data_get($item, 'bidNtceNm')),
            'awarded_company' => $this->nullableString(data_get($item, 'bidwinnrNm')) ?? $winner,
            'awarded_biz_no' => $this->nullableString(data_get($item, 'bidwinnrBizno')),
            'awarded_amount' => $this->toInteger(data_get($item, 'sucsfbidAmt')),
            'award_rate' => $this->toDecimal(data_get($item, 'sucsfbidRate')),
            'participant_count' => $this->toInteger(data_get($item, 'prtcptCnum')),
            'award_dt' => $this->parseDateTime(
                data_get($item, 'fnlSucsfDate')
                ?? data_get($item, 'rlOpengDt')
                ?? data_get($item, 'opengDt')
            ),
            'ranking' => [
                'rank' => $rank,
                'company_name' => $winner,
                'bid_amount' => $this->toInteger($this->firstFilled($item, [
                    'bidprcAmt',
                    'bidAmt',
                    'sucsfbidAmt',
                ])),
                'bid_rate' => $this->toDecimal($this->firstFilled($item, [
                    'bidprcRate',
                    'bidRate',
                    'sucsfbidRate',
                ])),
                'is_winner' => $this->toBoolean($this->firstFilled($item, [
                    'isWinner',
                    'bidWinnrYn',
                ])) ?? ($rank === 1 || $winner === $this->nullableString(data_get($item, 'bidwinnrNm'))),
            ],
            'raw' => $item,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function firstFilled(array $item, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($item, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function toInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        return is_numeric($normalized) ? (int) round((float) $normalized) : null;
    }

    protected function toDecimal(mixed $value, int $precision = 3): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        return is_numeric($normalized)
            ? number_format((float) $normalized, $precision, '.', '')
            : null;
    }

    protected function toBoolean(mixed $value): ?bool
    {
        return match ($value) {
            true, 1, '1', 'Y', 'y', 'true', 'TRUE' => true,
            false, 0, '0', 'N', 'n', 'false', 'FALSE' => false,
            default => null,
        };
    }

    protected function parseDateTime(mixed $value): ?string
    {
        $string = $this->nullableString($value);

        if ($string === null) {
            return null;
        }

        foreach (['Y-m-d H:i:s', 'YmdHis', '!Y-m-d', '!Ymd'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $string, config('app.timezone'))
                    ->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return CarbonImmutable::parse($string, config('app.timezone'))->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
