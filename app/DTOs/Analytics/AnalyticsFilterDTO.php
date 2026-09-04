<?php

namespace App\DTOs\Analytics;

class AnalyticsFilterDTO
{
    public function __construct(
        public readonly DateRangeDTO $dateRange,
        public readonly ?string $status = null,
        public readonly ?string $channel = null,
        public readonly ?string $mode = null,
        public readonly ?int $agentId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $campaignId = null,
        public readonly ?int $workflowId = null,
        public readonly ?array $tagIds = null,
        public readonly ?string $groupBy = 'day',
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'date_range' => $this->dateRange->toArray(),
            'status' => $this->status,
            'channel' => $this->channel,
            'mode' => $this->mode,
            'agent_id' => $this->agentId,
            'customer_id' => $this->customerId,
            'campaign_id' => $this->campaignId,
            'workflow_id' => $this->workflowId,
            'tag_ids' => $this->tagIds,
            'group_by' => $this->groupBy,
        ], fn($v) => $v !== null);
    }
}
