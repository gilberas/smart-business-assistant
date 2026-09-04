<?php

namespace App\Models;

use App\Enums\Broadcast\BroadcastRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BroadcastRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'customer_id',
        'uuid',
        'status',
        'sent_at',
        'queued_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
        'message_id',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'queued_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'status' => BroadcastRecipientStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $recipient) {
            if (empty($recipient->uuid)) {
                $recipient->uuid = (string) Str::uuid();
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BroadcastCampaign::class, 'campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
