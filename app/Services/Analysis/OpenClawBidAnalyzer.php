<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenClawBidAnalyzer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function analyze(array $payload): array
    {
        $baseUrl = rtrim((string) config('analysis.openclaw.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('OPENCLAW_BASE_URL is not configured.');
        }

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) config('analysis.openclaw.timeout', 120));

        $apiKey = (string) config('analysis.openclaw.api_key');

        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $response = $request->post((string) config('analysis.openclaw.endpoint', '/api/analyze-bid'), $payload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'OpenClaw analyze request failed with status %s: %s',
                $response->status(),
                str($response->body())->limit(300)->toString(),
            ));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Unexpected OpenClaw response format.');
        }

        return $data;
    }
}
