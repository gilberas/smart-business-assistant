<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\DTOs\Analytics\ChartDataDTO;
use App\DTOs\Analytics\DateRangeDTO;
use App\DTOs\Analytics\KpiDTO;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class BaseCalculator
{
    protected function getDateField(): string
    {
        return 'created_at';
    }

    protected function applyDateRange(Builder $query, AnalyticsFilterDTO $filter, ?string $dateField = null): Builder
    {
        $field = $dateField ?? $this->getDateField();
        return $query->whereBetween($field, [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);
    }

    protected function formatDate(\Carbon\Carbon $date, string $groupBy = 'day'): string
    {
        return match ($groupBy) {
            'hour' => $date->format('Y-m-d H:00'),
            'day' => $date->toDateString(),
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    protected function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    protected function dateDiffSql(string $column1, string $column2, string $unit): string
    {
        if ($this->isSqlite()) {
            $multiplier = match ($unit) {
                'SECOND' => 1,
                'MINUTE' => 60,
                'HOUR' => 3600,
                'DAY' => 86400,
                default => 1,
            };
            return "(CAST(strftime('%s', {$column2}) AS INTEGER) - CAST(strftime('%s', {$column1}) AS INTEGER)) / {$multiplier}";
        }
        return "TIMESTAMPDIFF({$unit}, {$column1}, {$column2})";
    }

    protected function dateFormatSql(string $column, string $format): string
    {
        if ($this->isSqlite()) {
            $map = [
                "'%Y-%m-%d %H:00'" => "'%Y-%m-%d %H:00'",
                "'%x-W%v'" => "'%Y-W%W'",
                "'%Y-%m'" => "'%Y-%m'",
            ];
            $sqliteFmt = $map[$format] ?? $format;
            return "strftime({$sqliteFmt}, {$column})";
        }
        return "DATE_FORMAT({$column}, {$format})";
    }

    protected function hourSql(string $column): string
    {
        if ($this->isSqlite()) {
            return "CAST(strftime('%H', {$column}) AS INTEGER)";
        }
        return "HOUR({$column})";
    }

    protected function dayNameSql(string $column): string
    {
        if ($this->isSqlite()) {
            return "CASE CAST(strftime('%w', {$column}) AS INTEGER) WHEN 0 THEN 'Sunday' WHEN 1 THEN 'Monday' WHEN 2 THEN 'Tuesday' WHEN 3 THEN 'Wednesday' WHEN 4 THEN 'Thursday' WHEN 5 THEN 'Friday' WHEN 6 THEN 'Saturday' END";
        }
        return "DAYNAME({$column})";
    }

    protected function timeSeries(Builder $query, string $column, string $groupBy = 'day', string $aggregate = 'COUNT'): array
    {
        $raw = match ($groupBy) {
            'hour' => $this->dateFormatSql($column, "'%Y-%m-%d %H:00'"),
            'day' => "DATE({$column})",
            'week' => $this->dateFormatSql($column, "'%x-W%v'"),
            'month' => $this->dateFormatSql($column, "'%Y-%m'"),
            default => "DATE({$column})",
        };

        $results = $query
            ->select(DB::raw("{$raw} as period"), DB::raw("{$aggregate}(*) as value"))
            ->whereNotNull($column)
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('value', 'period');

        return $results->toArray();
    }

    protected function cacheKey(Business $business, string $metric, AnalyticsFilterDTO $filter): string
    {
        $parts = [
            'analytics',
            $business->id,
            $metric,
            $filter->dateRange->preset,
            $filter->dateRange->start,
            $filter->dateRange->end,
        ];
        if ($filter->agentId) $parts[] = "agent:{$filter->agentId}";
        if ($filter->channel) $parts[] = "channel:{$filter->channel}";
        if ($filter->mode) $parts[] = "mode:{$filter->mode}";

        return implode(':', $parts);
    }

    protected function remember(Business $business, string $metric, AnalyticsFilterDTO $filter, \Closure $callback, int $ttl = 300): mixed
    {
        return Cache::remember($this->cacheKey($business, $metric, $filter), $ttl, $callback);
    }

    protected function previousPeriod(AnalyticsFilterDTO $filter): AnalyticsFilterDTO
    {
        $start = $filter->dateRange->startCarbon();
        $end = $filter->dateRange->endCarbon();
        $diff = $start->diffInDays($end) + 1;

        return new AnalyticsFilterDTO(
            dateRange: new DateRangeDTO(
                start: $start->copy()->subDays($diff)->toDateTimeString(),
                end: $end->copy()->subDays($diff)->toDateTimeString(),
                preset: $filter->dateRange->preset,
            ),
            status: $filter->status,
            channel: $filter->channel,
            mode: $filter->mode,
        );
    }

    protected function kpi(Business $business, string $key, string $label, \Closure $currentFn, ?\Closure $previousFn = null, string $format = 'number', ?string $icon = null): KpiDTO
    {
        $current = (int) $currentFn($business);
        $previous = $previousFn ? (int) $previousFn($business) : null;
        $change = ($previous && $previous > 0) ? round((($current - $previous) / $previous) * 100, 1) : null;

        return new KpiDTO(
            key: $key,
            label: $label,
            value: $current,
            previousValue: $previous,
            changePercent: $change,
            format: $format,
            icon: $icon,
        );
    }
}
