<?php

namespace App\Models\WhatsApp;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    use HasTenant;

    protected $table = 'delivery_logs';

    protected $fillable = [
        'business_id',
        'message_id',
        'event_type',
        'status',
        'response_code',
        'response_body',
        'attempted_at',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'duration_ms' => 'integer',
            'response_code' => 'integer',
        ];
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
