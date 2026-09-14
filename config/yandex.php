<?php

return [
    'user_agent' => env('YANDEX_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0'),

    'timeout'     => 30,
    'retry_times' => 3,
    'retry_sleep' => 1000,
    'page_size'   => 50,
    'max_pages'   => 15,
    'page_delay'  => 1_000_000,
];