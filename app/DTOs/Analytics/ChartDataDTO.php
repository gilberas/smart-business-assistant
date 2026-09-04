<?php

namespace App\DTOs\Analytics;

class ChartDataDTO
{
    public function __construct(
        public readonly array $labels,
        public readonly array $datasets,
        public readonly ?string $title = null,
        public readonly ?string $type = 'line',
    ) {}

    public function toArray(): array
    {
        return [
            'labels' => $this->labels,
            'datasets' => $this->datasets,
            'title' => $this->title,
            'type' => $this->type,
        ];
    }

    public static function fromTimeSeries(array $timeSeries, string $label, string $dataKey, string $type = 'line'): self
    {
        $labels = array_keys($timeSeries);
        $values = array_values(array_map(fn($v) => is_array($v) ? ($v[$dataKey] ?? 0) : $v, $timeSeries));

        return new self(
            labels: $labels,
            datasets: [
                ['label' => $label, 'data' => $values],
            ],
            type: $type,
        );
    }

    public static function fromMultiSeries(array $timeSeries, array $series, string $type = 'line'): self
    {
        $labels = array_keys($timeSeries);
        $datasets = [];

        foreach ($series as $key => $label) {
            $datasets[] = [
                'label' => $label,
                'data' => array_values(array_map(fn($v) => $v[$key] ?? 0, $timeSeries)),
            ];
        }

        return new self(labels: $labels, datasets: $datasets, type: $type);
    }
}
