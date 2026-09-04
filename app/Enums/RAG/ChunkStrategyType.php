<?php

namespace App\Enums\RAG;

enum ChunkStrategyType: string
{
    case Paragraph = 'paragraph';
    case Section = 'section';
    case SlidingWindow = 'sliding_window';
    case FixedSize = 'fixed_size';

    public function label(): string
    {
        return match ($this) {
            self::Paragraph => 'Paragraph',
            self::Section => 'Section',
            self::SlidingWindow => 'Sliding Window',
            self::FixedSize => 'Fixed Size',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Paragraph => 'Splits content by paragraph boundaries. Best for articles and FAQs.',
            self::Section => 'Splits content by headings/sections. Best for structured documents.',
            self::SlidingWindow => 'Overlapping windows of configurable size. Best for maximum context coverage.',
            self::FixedSize => 'Fixed-size chunks with overlap. Best for uniform processing.',
        };
    }
}
