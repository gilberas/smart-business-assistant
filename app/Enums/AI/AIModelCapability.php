<?php

namespace App\Enums\AI;

enum AIModelCapability: string
{
    case Text = 'text';
    case StructuredOutput = 'structured_output';
    case Streaming = 'streaming';
    case Vision = 'vision';
    case Audio = 'audio';
    case Embeddings = 'embeddings';
    case FunctionCalling = 'function_calling';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text Generation',
            self::StructuredOutput => 'Structured Output',
            self::Streaming => 'Streaming',
            self::Vision => 'Vision',
            self::Audio => 'Audio',
            self::Embeddings => 'Embeddings',
            self::FunctionCalling => 'Function Calling',
        };
    }
}
