<?php

$resultServiceKey = env('G2B_RESULT_SERVICE_KEY');

return [
    'bid_public' => [
        'base_url' => env('G2B_BASE_URL', 'https://apis.data.go.kr/1230000/ad/BidPublicInfoService'),
        'service_id' => '15129394',
        'service_key' => env('G2B_SERVICE_KEY'),
        'query' => [
            'inquiry_division' => env('G2B_PUBLIC_INQUIRY_DIV', '1'),
            'default_rows' => env('G2B_PUBLIC_NUM_OF_ROWS', 100),
            'max_pages' => env('G2B_PUBLIC_MAX_PAGES', 20),
            'max_window_days' => env('G2B_PUBLIC_MAX_WINDOW_DAYS', 30),
            'lookup_backtrack_days' => env('G2B_PUBLIC_LOOKUP_BACKTRACK_DAYS', 45),
            'lookup_forward_days' => env('G2B_PUBLIC_LOOKUP_FORWARD_DAYS', 7),
        ],
        'endpoints' => [
            'service' => 'getBidPblancListInfoServcPPSSrch',
            'goods' => 'getBidPblancListInfoThngPPSSrch',
            'etc' => 'getBidPblancListInfoEtcPPSSrch',
        ],
    ],
    'bid_result' => [
        'base_url' => env('G2B_RESULT_BASE_URL', 'https://apis.data.go.kr/1230000/as/ScsbidInfoService'),
        'service_id' => '15129397',
        'service_key' => $resultServiceKey !== null && $resultServiceKey !== ''
            ? $resultServiceKey
            : env('G2B_SERVICE_KEY'),
        'endpoints' => [
            'service' => 'getScsbidListSttusServc',
            'goods' => 'getScsbidListSttusThng',
            'etc' => 'getScsbidListSttusEtc',
        ],
        'inquiry_division' => env('G2B_RESULT_INQUIRY_DIV', '4'),
        'default_rows' => env('G2B_RESULT_NUM_OF_ROWS', 20),
        'max_window_days' => env('G2B_RESULT_MAX_WINDOW_DAYS', 120),
        'timeout' => (int) env('G2B_TIMEOUT', 10),
        'connect_timeout' => (int) env('G2B_CONNECT_TIMEOUT', 5),
        'retry_sleep_ms' => [200, 500, 1000],
    ],
    'documents' => [
        'disk' => env('G2B_DOWNLOAD_DISK', 'local'),
        'path' => env('G2B_DOWNLOAD_PATH', 'bid-documents'),
        'download_referer' => 'https://www.g2b.go.kr/',
        'user_agent' => env('G2B_DOWNLOAD_USER_AGENT', 'GAR/1.0'),
    ],
];
