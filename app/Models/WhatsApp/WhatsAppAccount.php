<?php

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\TokenStatus;
use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Models\Business;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WhatsAppAccount extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'uuid',
        'business_id',
        'waba_id',
        'access_token',
        'token_status',
        'token_expires_at',
        'webhook_secret',
        'webhook_verified_at',
        'last_webhook_received_at',
        'metadata',
        'status',
        'connected_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'metadata' => 'array',
            'token_expires_at' => 'datetime',
            'webhook_verified_at' => 'datetime',
            'last_webhook_received_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'token_status' => TokenStatus::class,
            'status' => WhatsAppAccountStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WhatsAppAccount $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class, 'whatsapp_account_id');
    }

    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class, 'whatsapp_account_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'whatsapp_account_id');
    }

    public function scopeConnected($query)
    {
        return $query->where('status', WhatsAppAccountStatus::Connected);
    }

    public function scopeTokenExpiring($query, int $days = 14)
    {
        return $query->where('token_status', TokenStatus::Active)
            ->where('token_expires_at', '<=', now()->addDays($days));
    }

    public function isConnected(): bool
    {
        return $this->status === WhatsAppAccountStatus::Connected;
    }
}
