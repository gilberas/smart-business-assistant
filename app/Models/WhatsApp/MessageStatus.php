<?php

namespace App\Models\WhatsApp;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class MessageStatus extends Model
{
    use HasTenant;

    protected $table = 'message_statuses';

    protected $fillable = [
        'business_id',
        'message_id',
        'status',
        'meta_status',
        'timestamp',
        'error_code',
        'error_title',
        'error_message',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'details' => 'array',
        ];
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
