<?php

namespace App\Console\Commands;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Services\Documents\BidDocumentTextSynchronizer;
use App\Services\G2B\BidDocumentSynchronizer;
use App\Services\G2B\G2BDocumentDownloader;
use Illuminate\Console\Command;
use Throwable;

class SyncBidDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'g2b:sync-bid-documents
        {--bid=* : Specific bid notice numbers to sync}
        {--ord=000 : Bid notice order when using --bid}
        {--limit=20 : Maximum number of bids to process}
        {--force-sync : Re-query notice metadata even if raw_data exists}
        {--force-download : Re-download files that already exist}
        {--force-parse : Re-parse files that already have extracted text}
        {--no-parse : Skip text extraction after download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync bid attachments from G2B, download them, and extract text when supported.';

    /**
     * Execute the console command.
     */
    public function handle(
        BidDocumentSynchronizer $synchronizer,
        G2BDocumentDownloader $downloader,
        BidDocumentTextSynchronizer $textSynchronizer,
    ): int {
        $bids = $this->resolveTargetBids();

        if ($bids->isEmpty()) {
            $this->components->info('No bids matched the document sync criteria.');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($bids as $bid) {
            try {
                $documents = $this->option('force-sync')
                    ? $synchronizer->sync($bid, null, null)
                    : $synchronizer->sync($bid);

                foreach ($documents as $document) {
                    $document = $downloader->download($document, (bool) $this->option('force-download'));

                    if (! $this->option('no-parse')) {
                        try {
                            $textSynchronizer->extract($document, (bool) $this->option('force-parse'));
                        } catch (Throwable $exception) {
                            $this->warn(sprintf(
                                '%s/%s doc #%d parse skipped: %s',
                                $bid->bid_ntce_no,
                                $bid->bid_ntce_ord,
                                $document->seq,
                                $exception->getMessage(),
                            ));
                        }
                    }
                }

                if ($documents->isNotEmpty()) {
                    $bid->update([
                        'pipeline_status' => 'documents_ready',
                    ]);
                }

                $synced++;
                $this->line(sprintf(
                    '%s/%s -> %d document(s)',
                    $bid->bid_ntce_no,
                    $bid->bid_ntce_ord,
                    $documents->count(),
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
        $this->components->info(sprintf('Synced documents for %d bid(s), %d failed.', $synced, $failed));

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
        }

        return $query->limit(max(1, (int) $this->option('limit')))->get();
    }
}
