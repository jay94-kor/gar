<?php

namespace App\Console\Commands;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Services\G2B\BidResultSynchronizer;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncBidResultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'g2b:sync-bid-results
        {--bid=* : Specific bid notice numbers to sync}
        {--ord=000 : Bid notice order when using --bid}
        {--limit=50 : Maximum number of bids to process}
        {--days=120 : Maximum lookup window in days}
        {--force : Re-fetch bids that already have synced results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync awarded bid results from the G2B result API.';

    /**
     * Execute the console command.
     */
    public function handle(BidResultSynchronizer $synchronizer): int
    {
        if ((string) config('g2b.bid_result.service_key') === '') {
            $this->components->error('G2B bid result service key is not configured.');

            return self::FAILURE;
        }

        $bids = $this->resolveTargetBids();

        if ($bids->isEmpty()) {
            $this->components->info('No bids matched the sync criteria.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Syncing %d bid(s) from G2B bid result API...', $bids->count()));

        $synced = 0;
        $failed = 0;

        foreach ($bids as $bid) {
            try {
                $window = $this->resolveWindow($bid);
                $result = $synchronizer->sync($bid, $window['from'], $window['to']);
                $synced++;

                $this->line(sprintf(
                    '%s/%s -> %s (%s)',
                    $bid->bid_ntce_no,
                    $bid->bid_ntce_ord,
                    $result->result_status->value,
                    $result->awarded_company ?? 'winner unknown',
                ));
            } catch (Throwable $exception) {
                $failed++;
                report($exception);

                $this->error(sprintf(
                    '%s/%s failed: %s',
                    $bid->bid_ntce_no,
                    $bid->bid_ntce_ord,
                    $exception->getMessage(),
                ));
            }
        }

        $this->newLine();
        $this->components->info(sprintf('Synced %d bid result(s), %d failed.', $synced, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function resolveTargetBids()
    {
        $explicitBidNumbers = array_values(array_filter((array) $this->option('bid')));

        $query = Bid::query()
            ->whereIn('status', [
                BidStatus::Open->value,
                BidStatus::Closed->value,
                BidStatus::Awarded->value,
            ])
            ->orderBy('bid_close_dt')
            ->orderBy('id');

        if ($explicitBidNumbers !== []) {
            $query->whereIn('bid_ntce_no', $explicitBidNumbers)
                ->where('bid_ntce_ord', (string) $this->option('ord'));
        } else {
            $query->where(function ($builder) {
                $builder->whereNull('bid_close_dt')
                    ->orWhere('bid_close_dt', '<=', now()->subDays(7));
            });
        }

        if (! $this->option('force')) {
            $query->doesntHave('result');
        }

        return $query->limit(max(1, (int) $this->option('limit')))->get();
    }

    protected function resolveWindow(Bid $bid): array
    {
        $reference = $bid->bid_close_dt ?? $bid->bid_open_dt ?? now();
        $from = CarbonImmutable::parse($reference)->subDays(7)->startOfDay();
        $to = $from->addDays(max(1, (int) $this->option('days')) - 1)->endOfDay();
        $now = CarbonImmutable::now()->endOfDay();

        if ($to->greaterThan($now)) {
            $to = $now;
        }

        return [
            'from' => $from,
            'to' => $to->lessThan($from) ? $from->endOfDay() : $to,
        ];
    }
}
