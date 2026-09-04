<?php

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\PhoneNumberStatus;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PhoneNumber extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $table = 'whatsapp_phone_numbers';

    protected $fillable = [
        'uuid',
        'whatsapp_account_id',
        'business_id',
        'phone_number',
        'phone_number_id',
        'display_phone',
        'display_phone_number',
        'display_name',
        'certificate',
        'quality_rating',
        'status',
        'verified_name',
        'code_verification_status',
        'pin_enabled',
        'messaging_limit_tier',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'pin_enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'status' => PhoneNumberStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PhoneNumber $phoneNumber) {
            if (empty($phoneNumber->uuid)) {
                $phoneNumber->uuid = (string) Str::uuid();
            }
        });
    }

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'phone_number_id', 'phone_number_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', PhoneNumberStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === PhoneNumberStatus::Active;
    }
}
