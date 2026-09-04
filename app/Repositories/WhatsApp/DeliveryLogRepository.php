<?php

namespace App\Repositories\WhatsApp;

use App\Models\WhatsApp\DeliveryLog;

class DeliveryLogRepository
{
    public function create(array $data): DeliveryLog
    {
        return DeliveryLog::create($data);
    }

    public function getByMessageId(int $messageId): iterable
    {
        return DeliveryLog::where('message_id', $messageId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getFailedDeliveries(int $businessId, int $limit = 20): iterable
    {
        return DeliveryLog::where('business_id', $businessId)
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
