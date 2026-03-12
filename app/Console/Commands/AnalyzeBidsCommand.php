<?php

namespace App\Console\Commands;

use App\Models\Bid;
use App\Services\Analysis\BidAnalysisSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class AnalyzeBidsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bids:analyze
        {--bid=* : Specific bid notice numbers to analyze}
        {--ord=000 : Bid notice order when using --bid}
        {--limit=20 : Maximum number of bids to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send parsed bid documents to the analysis provider and persist the structured result.';

    /**
     * Execute the console command.
     */
    public function handle(BidAnalysisSynchronizer $synchronizer): int
    {
        $bids = $this->resolveTargetBids();

        if ($bids->isEmpty()) {
            $this->components->info('No bids matched the analysis criteria.');

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        foreach ($bids as $bid) {
            try {
                $analysis = $synchronizer->analyze($bid);
                $processed++;

                $this->line(sprintf(
                    '%s/%s -> %s (v%d)',
                    $bid->bid_ntce_no,
                    $bid->bid_ntce_ord,
                    $analysis->status,
                    $analysis->analysis_version,
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
        $this->components->info(sprintf('Analyzed %d bid(s), %d failed.', $processed, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function resolveTargetBids()
    {
        $explicitBidNumbers = array_values(array_filter((array) $this->option('bid')));

        $query = Bid::query()
            ->whereHas('documents', fn ($builder) => $builder->whereNotNull('extracted_text'))
            ->orderBy('bid_close_dt')
            ->orderBy('id');

        if ($explicitBidNumbers !== []) {
            $query->whereIn('bid_ntce_no', $explicitBidNumbers)
                ->where('bid_ntce_ord', (string) $this->option('ord'));
        }

        return $query->limit(max(1, (int) $this->option('limit')))->get();
    }
}
