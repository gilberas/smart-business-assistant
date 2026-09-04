<?php

namespace App\DTOs\Analytics;

class DateRangeDTO
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
        public readonly string $preset = 'last_30_days',
    ) {}

    public static function fromPreset(string $preset): self
    {
        $end = now();
        $start = match ($preset) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(6)->startOfDay(),
            'last_30_days' => now()->subDays(29)->startOfDay(),
            'last_90_days' => now()->subDays(89)->startOfDay(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth()->startOfDay(),
            'all_time' => now()->subYears(10)->startOfDay(),
            default => now()->subDays(29)->startOfDay(),
        };

        if ($preset === 'last_month') {
            $end = now()->subMonth()->endOfMonth()->endOfDay();
        }

        return new self(
            start: $start->toDateTimeString(),
            end: $end->toDateTimeString(),
            preset: $preset,
        );
    }

    public static function fromRange(string $start, string $end): self
    {
        return new self(
            start: $start,
            end: $end,
            preset: 'custom',
        );
    }

    public function startCarbon(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->start);
    }

    public function endCarbon(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->end);
    }

    public function toArray(): array
    {
        return ['start' => $this->start, 'end' => $this->end, 'preset' => $this->preset];
    }
}
