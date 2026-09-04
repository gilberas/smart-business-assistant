<?php

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WebhookEventStatus;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookEvent extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'uuid',
        'business_id',
        'whatsapp_account_id',
        'phone_number_id',
        'event_type',
        'meta_event_id',
        'meta_changed_field',
        'meta_resource',
        'status',
        'payload',
        'headers',
        'signature',
        'retry_count',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'payload' => 'array',
            'headers' => 'array',
            'processed_at' => 'datetime',
            'status' => WebhookEventStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WebhookEvent $event) {
            if (empty($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', WebhookEventStatus::Pending);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', WebhookEventStatus::Failed);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => WebhookEventStatus::Failed,
            'last_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function markAsDeadLetter(): void
    {
        $this->update(['status' => WebhookEventStatus::DeadLetter]);
    }
}
