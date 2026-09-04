<?php

namespace App\DTOs\Analytics;

class KpiDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly float|int $value,
        public readonly ?float $previousValue = null,
        public readonly ?float $changePercent = null,
        public readonly ?string $format = 'number',
        public readonly ?string $icon = null,
        public readonly ?string $tooltip = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'value' => $this->value,
            'previous_value' => $this->previousValue,
            'change_percent' => $this->changePercent,
            'format' => $this->format,
            'icon' => $this->icon,
            'tooltip' => $this->tooltip,
        ];
    }
}
