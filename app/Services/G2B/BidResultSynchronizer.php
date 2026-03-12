<?php

namespace App\Services\G2B;

use App\Enums\BidResultStatus;
use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidResult;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BidResultSynchronizer
{
    public function __construct(
        protected BidResultClient $client,
    ) {
    }

    public function sync(Bid $bid, ?CarbonInterface $from = null, ?CarbonInterface $to = null): BidResult
    {
        $window = $this->resolveWindow($bid, $from, $to);
        $payload = $this->client->fetchServiceResults(
            bidNtceNo: $bid->bid_ntce_no,
            bidNtceOrd: $bid->bid_ntce_ord,
            from: $window['from'],
            to: $window['to'],
        );

        $primary = $this->selectPrimaryItem($payload['items']);
        $status = $this->resolveResultStatus($primary);

        return DB::transaction(function () use ($bid, $payload, $primary, $status, $window): BidResult {
            $result = BidResult::query()->updateOrCreate(
                ['bid_id' => $bid->id],
                [
                    'result_status' => $status,
                    'awarded_company' => $primary['awarded_company'] ?? null,
                    'awarded_biz_no' => $primary['awarded_biz_no'] ?? null,
                    'awarded_amount' => $primary['awarded_amount'] ?? null,
                    'award_rate' => $primary['award_rate'] ?? null,
                    'participant_count' => $primary['participant_count'] ?? null,
                    'award_dt' => $primary['award_dt'] ?? null,
                    'raw_data' => [
                        'request' => $this->sanitizeRequestPayload($payload['request']),
                        'window' => [
                            'from' => $window['from']->toDateString(),
                            'to' => $window['to']->toDateString(),
                        ],
                        'response' => $payload['raw'],
                    ],
                ],
            );

            $result->rankings()->delete();

            $rankings = $this->buildRankings($payload['items'], $primary);

            if ($rankings !== []) {
                $result->rankings()->createMany($rankings);
            }

            $bid->update([
                'status' => $this->mapBidStatus($status),
            ]);

            return $result->fresh('rankings');
        });
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
        $windowDays = max(1, (int) config('g2b.bid_result.max_window_days', 120));
        $windowStart = CarbonImmutable::parse($reference)->subDays(7)->startOfDay();
        $windowEnd = $windowStart->addDays($windowDays - 1)->endOfDay();
        $now = CarbonImmutable::now()->endOfDay();

        if ($windowEnd->greaterThan($now)) {
            $windowEnd = $now;
        }

        if ($windowEnd->lessThan($windowStart)) {
            $windowEnd = $windowStart->endOfDay();
        }

        return [
            'from' => $windowStart,
            'to' => $windowEnd,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    protected function selectPrimaryItem(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        usort($items, function (array $left, array $right): int {
            $leftWinner = ($left['ranking']['is_winner'] ?? false) ? 0 : 1;
            $rightWinner = ($right['ranking']['is_winner'] ?? false) ? 0 : 1;

            if ($leftWinner !== $rightWinner) {
                return $leftWinner <=> $rightWinner;
            }

            return ($left['ranking']['rank'] ?? PHP_INT_MAX) <=> ($right['ranking']['rank'] ?? PHP_INT_MAX);
        });

        return $items[0];
    }

    /**
     * @param  array<string, mixed>|null  $primary
     */
    protected function resolveResultStatus(?array $primary): BidResultStatus
    {
        if ($primary === null) {
            return BidResultStatus::Unknown;
        }

        if (($primary['awarded_company'] ?? null) !== null || ($primary['awarded_amount'] ?? null) !== null) {
            return BidResultStatus::Awarded;
        }

        $rebidNo = trim((string) data_get($primary, 'raw.rbidNo', ''));

        if ($rebidNo !== '' && ! in_array($rebidNo, ['0', '000'], true)) {
            return BidResultStatus::Rebid;
        }

        return BidResultStatus::Unknown;
    }

    protected function mapBidStatus(BidResultStatus $status): BidStatus
    {
        return match ($status) {
            BidResultStatus::Awarded => BidStatus::Awarded,
            BidResultStatus::Cancelled => BidStatus::Cancelled,
            default => BidStatus::Closed,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>|null  $primary
     * @return array<int, array<string, mixed>>
     */
    protected function buildRankings(array $items, ?array $primary): array
    {
        $rows = [];

        foreach ($items as $index => $item) {
            $ranking = $item['ranking'] ?? [];
            $companyName = $ranking['company_name'] ?? $item['awarded_company'] ?? null;
            $bidAmount = $ranking['bid_amount'] ?? $item['awarded_amount'] ?? null;
            $bidRate = $ranking['bid_rate'] ?? $item['award_rate'] ?? null;

            if ($companyName === null && $bidAmount === null && $bidRate === null) {
                continue;
            }

            $rows[] = [
                'rank' => $ranking['rank'] ?? ($index + 1),
                'company_name' => $companyName,
                'bid_amount' => $bidAmount,
                'bid_rate' => $bidRate,
                'is_winner' => (bool) ($ranking['is_winner'] ?? false),
            ];
        }

        if ($rows === [] && $primary !== null) {
            $hasWinnerSnapshot = ($primary['awarded_company'] ?? null) !== null
                || ($primary['awarded_amount'] ?? null) !== null
                || ($primary['award_rate'] ?? null) !== null;

            if ($hasWinnerSnapshot) {
                $rows[] = [
                    'rank' => 1,
                    'company_name' => $primary['awarded_company'] ?? null,
                    'bid_amount' => $primary['awarded_amount'] ?? null,
                    'bid_rate' => $primary['award_rate'] ?? null,
                    'is_winner' => true,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function sanitizeRequestPayload(array $request): array
    {
        unset($request['serviceKey']);

        return $request;
    }
}
