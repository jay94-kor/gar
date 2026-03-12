<?php

return [
    'provider' => env('ANALYSIS_PROVIDER', 'openclaw'),
    'schema_version' => env('ANALYSIS_SCHEMA_VERSION', 'v1'),
    'prompt_version' => env('ANALYSIS_PROMPT_VERSION', 'analysis-master-v1'),
    'openclaw' => [
        'base_url' => env('OPENCLAW_BASE_URL'),
        'endpoint' => env('OPENCLAW_ANALYZE_ENDPOINT', '/api/analyze-bid'),
        'api_key' => env('OPENCLAW_API_KEY'),
        'timeout' => (int) env('OPENCLAW_TIMEOUT', 120),
    ],
];
