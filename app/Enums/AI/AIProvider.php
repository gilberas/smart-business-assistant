<?php

namespace App\Enums\AI;

enum AIProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Google = 'google';
    case OpenRouter = 'openrouter';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::Anthropic => 'Anthropic Claude',
            self::Google => 'Google Gemini',
            self::OpenRouter => 'OpenRouter',
        };
    }

    public function baseUrl(): string
    {
        return match ($this) {
            self::OpenAI => 'https://api.openai.com/v1',
            self::Anthropic => 'https://api.anthropic.com/v1',
            self::Google => 'https://generativelanguage.googleapis.com/v1',
            self::OpenRouter => 'https://openrouter.ai/api/v1',
        };
    }

    public function defaultModel(): string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4o',
            self::Anthropic => 'claude-3-5-sonnet-20241022',
            self::Google => 'gemini-2.0-flash',
            self::OpenRouter => 'openai/gpt-4o',
        };
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function supportsStructuredOutput(): bool
    {
        return match ($this) {
            self::OpenAI => true,
            self::Anthropic => true,
            self::Google => true,
            self::OpenRouter => true,
        };
    }
}
