<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappPhoneNumber extends Model
{
    protected $fillable = [
        'business_id',
        'phone_number',
        'display_phone_number',
        'verified_name',
        'quality_rating',
        'status',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
