<?php

namespace App\Services\Analysis;

use App\Models\Bid;
use RuntimeException;

class BidAnalysisPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Bid $bid): array
    {
        $documents = $bid->documents()
            ->whereNotNull('extracted_text')
            ->orderBy('seq')
            ->get();

        if ($documents->isEmpty()) {
            throw new RuntimeException('No parsed bid documents are available for analysis.');
        }

        $combinedText = $documents->pluck('extracted_text')
            ->filter()
            ->implode("\n\n---\n\n");

        return [
            'meta' => [
                'schema_version' => (string) config('analysis.schema_version', 'v1'),
                'prompt_version' => (string) config('analysis.prompt_version', 'analysis-master-v1'),
                'provider' => (string) config('analysis.provider', 'openclaw'),
            ],
            'bid' => [
                'id' => $bid->id,
                'bid_ntce_no' => $bid->bid_ntce_no,
                'bid_ntce_ord' => $bid->bid_ntce_ord,
                'title' => $bid->title,
                'institution' => $bid->institution,
                'classification_code' => $bid->classification_code,
                'budget' => $bid->budget,
                'bid_open_dt' => optional($bid->bid_open_dt)?->toDateTimeString(),
                'bid_close_dt' => optional($bid->bid_close_dt)?->toDateTimeString(),
            ],
            'documents' => $documents->map(fn ($document) => [
                'id' => $document->id,
                'seq' => $document->seq,
                'filename' => $document->filename,
                'file_type' => $document->file_type?->value,
                'text' => $document->extracted_text,
            ])->values()->all(),
            'combined_text' => $combinedText,
        ];
    }
}
