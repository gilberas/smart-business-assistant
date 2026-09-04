<?php

return [
    'decision' => [
        'auto_reply_enabled' => (bool) env('INBOX_AUTO_REPLY_ENABLED', false),
        'review_before_send' => (bool) env('INBOX_REVIEW_BEFORE_SEND', true),
        'escalate_sensitive_topics' => (bool) env('INBOX_ESCALATE_SENSITIVE', true),
        'auto_reply_confidence' => (float) env('INBOX_AUTO_REPLY_CONFIDENCE', 0.85),
        'review_confidence' => (float) env('INBOX_REVIEW_CONFIDENCE', 0.60),
        'unknown_question_confidence' => (float) env('INBOX_UNKNOWN_QUESTION_CONFIDENCE', 0.45),
        'business_hours_only' => (bool) env('INBOX_BUSINESS_HOURS_ONLY', false),
        'sensitive_topics' => [
            'refund', 'chargeback', 'legal', 'lawsuit', 'complaint', 'abuse', 'fraud',
            'payment failure', 'identity', 'password', 'security breach',
        ],
        'never_reply_topics' => [
            'complaint escalation', 'legal request', 'account deletion', 'data export',
        ],
    ],
    'typing_ttl' => (int) env('INBOX_TYPING_TTL', 12),
    'presence_ttl' => (int) env('INBOX_PRESENCE_TTL', 30),
    'draft' => [
        'max_length' => (int) env('INBOX_DRAFT_MAX_LENGTH', 10000),
    ],
];