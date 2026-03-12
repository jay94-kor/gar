<?php

namespace App\Services\Analysis;

use App\Models\Bid;
use App\Models\BidAnalysis;

class BidAnalysisSynchronizer
{
    public function __construct(
        protected BidAnalysisPayloadBuilder $payloadBuilder,
        protected OpenClawBidAnalyzer $analyzer,
        protected BidAnalysisValidator $validator,
        protected BidAnalysisPersister $persister,
    ) {
    }

    public function analyze(Bid $bid): BidAnalysis
    {
        $payload = $this->payloadBuilder->build($bid);
        $analysis = $this->analyzer->analyze($payload);

        $this->validator->validate($analysis);

        return $this->persister->persist(
            bid: $bid,
            analysis: $analysis,
            inputHash: hash('sha256', (string) ($payload['combined_text'] ?? '')),
        );
    }
}
