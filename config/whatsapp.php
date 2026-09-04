<?php

return [
    'api' => [
        'base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/v22.0'),
        'version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'timeout' => env('WHATSAPP_API_TIMEOUT', 30),
        'retry_on_failure' => env('WHATSAPP_API_RETRY_ON_FAILURE', true),
        'max_retries' => env('WHATSAPP_API_MAX_RETRIES', 3),
    ],

    'webhook' => [
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'signature_header' => 'X-Hub-Signature-256',
        'max_payload_size' => 1024 * 1024,
    ],

    'media' => [
        'disk' => env('WHATSAPP_MEDIA_DISK', 'local'),
        'path' => env('WHATSAPP_MEDIA_PATH', 'whatsapp/media'),
        'max_size' => env('WHATSAPP_MEDIA_MAX_SIZE', 100 * 1024 * 1024),
        'temp_url_ttl' => env('WHATSAPP_MEDIA_TEMP_URL_TTL', 3600),
        'cleanup_days' => env('WHATSAPP_MEDIA_CLEANUP_DAYS', 7),
    ],

    'queues' => [
        'webhook' => env('WHATSAPP_QUEUE_WEBHOOK', 'webhook'),
        'outgoing' => env('WHATSAPP_QUEUE_OUTGOING', 'outgoing'),
        'media' => env('WHATSAPP_QUEUE_MEDIA', 'media'),
    ],

    'rate_limiting' => [
        'messages_per_second' => env('WHATSAPP_RATE_LIMIT_MESSAGES', 50),
        'phone_numbers_per_request' => env('WHATSAPP_RATE_LIMIT_PHONES', 10),
    ],

    'token' => [
        'encryption' => env('WHATSAPP_TOKEN_ENCRYPTION', true),
        'rotation_days' => env('WHATSAPP_TOKEN_ROTATION_DAYS', 60),
        'expiration_warning_days' => env('WHATSAPP_TOKEN_EXPIRATION_WARNING', 14),
    ],

    'sandbox' => [
        'enabled' => env('WHATSAPP_SANDBOX_ENABLED', false),
        'phone_number' => env('WHATSAPP_SANDBOX_PHONE'),
    ],

    'mock' => [
        'enabled' => env('WHATSAPP_MOCK_ENABLED', false),
        'provider' => env('WHATSAPP_MOCK_PROVIDER', 'local'),
    ],

    'conversation_engine' => [
        'enabled' => env('CONVERSATION_ENGINE_ENABLED', false),
        'driver' => env('CONVERSATION_ENGINE_DRIVER', 'null'),
    ],
];
