<?php

$resultServiceKey = env('G2B_RESULT_SERVICE_KEY');

return [
    'bid_public' => [
        'base_url' => env('G2B_BASE_URL', 'https://apis.data.go.kr/1230000/ad/BidPublicInfoService'),
        'service_id' => '15129394',
        'service_key' => env('G2B_SERVICE_KEY'),
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
];
