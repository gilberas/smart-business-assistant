<?php

namespace App\DTOs\Broadcast;

class CampaignStatsDTO
{
    public function __construct(
        public readonly int $total,
        public readonly int $pending,
        public readonly int $queued,
        public readonly int $sent,
        public readonly int $delivered,
        public readonly int $read,
        public readonly int $failed,
        public readonly int $expired,
        public readonly int $rejected,
        public readonly float $deliveryRate,
        public readonly float $readRate,
    ) {}

    public static function fromCounts(int $total, array $statusCounts): self
    {
        $pending = $statusCounts['pending'] ?? 0;
        $queued = $statusCounts['queued'] ?? 0;
        $sent = $statusCounts['sent'] ?? 0;
        $delivered = $statusCounts['delivered'] ?? 0;
        $read = $statusCounts['read'] ?? 0;
        $failed = $statusCounts['failed'] ?? 0;
        $expired = $statusCounts['expired'] ?? 0;
        $rejected = $statusCounts['rejected'] ?? 0;
        $sentOrMore = $sent + $delivered + $read;

        return new self(
            total: $total,
            pending: $pending,
            queued: $queued,
            sent: $sent,
            delivered: $delivered,
            read: $read,
            failed: $failed,
            expired: $expired,
            rejected: $rejected,
            deliveryRate: $total > 0 ? round(($sentOrMore / $total) * 100, 2) : 0,
            readRate: $total > 0 ? round(($read / $total) * 100, 2) : 0,
        );
    }
}
