<?php

return [
    'defaults' => [
        'provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
        'model' => env('AI_DEFAULT_MODEL', 'gpt-4o'),
        'temperature' => (float) env('AI_DEFAULT_TEMPERATURE', 0.7),
        'top_p' => (float) env('AI_DEFAULT_TOP_P', 1.0),
        'max_tokens' => (int) env('AI_DEFAULT_MAX_TOKENS', 2048),
        'timeout' => (int) env('AI_DEFAULT_TIMEOUT', 30),
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', ''),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY', ''),
        ],
        'google' => [
            'api_key' => env('GEMINI_API_KEY', ''),
        ],
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY', ''),
        ],
    ],

    'pricing' => [
        'default' => ['input' => 0, 'output' => 0],
        'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
        'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
        'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        'claude-3-5-sonnet-20241022' => ['input' => 0.003, 'output' => 0.015],
        'claude-3-5-haiku-20241022' => ['input' => 0.00025, 'output' => 0.00125],
        'claude-3-opus-20240229' => ['input' => 0.015, 'output' => 0.075],
        'gemini-2.0-flash' => ['input' => 0.0001, 'output' => 0.0004],
        'gemini-2.0-pro' => ['input' => 0.001, 'output' => 0.002],
    ],

    'rate_limits' => [
        'requests_per_minute' => (int) env('AI_RATE_LIMIT_RPM', 60),
        'tokens_per_minute' => (int) env('AI_RATE_LIMIT_TPM', 100000),
    ],

    'cache' => [
        'models_ttl' => 3600,
        'pricing_ttl' => 86400,
        'health_ttl' => 300,
    ],
];
