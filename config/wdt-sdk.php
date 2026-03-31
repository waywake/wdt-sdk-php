<?php

declare(strict_types=1);

return [
    'default' => env('WDT_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'url' => env('WDT_URL', 'http://wdt.wangdian.cn'),
            'sid' => env('WDT_SID', ''),
            'key' => env('WDT_KEY', ''),
            'secret' => env('WDT_SECRET', ''),
            'multi_tenant_mode' => (bool) env('WDT_MULTI_TENANT_MODE', false),
        ],
    ],
];
