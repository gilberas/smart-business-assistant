<?php

return [
    'default_execution_mode' => env('WORKFLOW_DEFAULT_EXECUTION_MODE', 'sequential'),

    'max_attempts' => (int) env('WORKFLOW_MAX_ATTEMPTS', 3),

    'action_timeout' => (int) env('WORKFLOW_ACTION_TIMEOUT', 30),

    'max_actions_per_workflow' => (int) env('WORKFLOW_MAX_ACTIONS', 50),

    'max_conditions_per_group' => (int) env('WORKFLOW_MAX_CONDITIONS', 20),

    'retry_delays' => [1, 5, 15],

    'scheduled' => [
        'check_every_minutes' => (int) env('WORKFLOW_SCHEDULED_CHECK_MINUTES', 1),
    ],

    'limits' => [
        'max_concurrent_runs' => (int) env('WORKFLOW_MAX_CONCURRENT_RUNS', 10),
        'max_execution_time_seconds' => (int) env('WORKFLOW_MAX_EXECUTION_TIME', 300),
        'max_payload_size_bytes' => (int) env('WORKFLOW_MAX_PAYLOAD_SIZE', 1048576),
    ],

    'security' => [
        'allowed_webhook_domains' => explode(',', env('WORKFLOW_ALLOWED_WEBHOOK_DOMAINS', '')),
        'blocked_private_ips' => true,
    ],
];
