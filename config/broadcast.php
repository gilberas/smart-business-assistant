<?php

return [
    'sending' => [
        'batch_size' => (int) env('BROADCAST_BATCH_SIZE', 100),
        'per_second' => (int) env('BROADCAST_PER_SECOND', 10),
        'max_recipients' => (int) env('BROADCAST_MAX_RECIPIENTS', 100000),
        'retry_on_failure' => env('BROADCAST_RETRY_ON_FAILURE', true),
        'max_retries' => (int) env('BROADCAST_MAX_RETRIES', 3),
        'retry_delay_minutes' => (int) env('BROADCAST_RETRY_DELAY', 5),
    ],

    'limits' => [
        'daily_per_business' => (int) env('BROADCAST_DAILY_LIMIT', 10000),
        'concurrent_campaigns' => (int) env('BROADCAST_CONCURRENT_LIMIT', 3),
        'pause_on_failure_threshold' => (int) env('BROADCAST_FAILURE_THRESHOLD', 0.1),
    ],

    'scheduling' => [
        'check_every_minutes' => (int) env('BROADCAST_SCHEDULED_CHECK_MINUTES', 1),
        'max_delay_days' => (int) env('BROADCAST_MAX_DELAY_DAYS', 30),
    ],

    'audience' => [
        'max_preview' => (int) env('BROADCAST_AUDIENCE_PREVIEW', 50),
    ],

    'templates' => [
        'sync_on_boot' => env('BROADCAST_TEMPLATE_SYNC_ON_BOOT', false),
        'default_language' => env('BROADCAST_DEFAULT_LANGUAGE', 'en_US'),
    ],

    'queues' => [
        'broadcast' => env('BROADCAST_QUEUE', 'broadcast'),
        'delivery' => env('BROADCAST_DELIVERY_QUEUE', 'delivery'),
    ],
];
