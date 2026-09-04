<?php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_hours',
        'ai_provider',
        'ai_model',
        'ai_temperature',
        'ai_max_tokens',
        'ai_system_prompt',
        'whatsapp_business_account_id',
        'whatsapp_access_token',
        'whatsapp_phone_number_id',
        'webhook_verify_token',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'ai_temperature' => 'decimal:2',
            'ai_max_tokens' => 'integer',
            'settings' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
