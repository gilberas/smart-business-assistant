<?php

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\MessageDirection;
use App\Enums\WhatsApp\MessageStatus;
use App\Enums\WhatsApp\MessageType;
use App\Models\WhatsApp\MessageStatus as MessageStatusModel;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $table = 'messages';

    protected $fillable = [
        'uuid',
        'business_id',
        'conversation_id',
        'whatsapp_account_id',
        'phone_number_id',
        'from_number',
        'to_number',
        'direction',
        'message_type',
        'meta_message_id',
        'conversation_uuid',
        'status',
        'sender_type',
        'sender_id',
        'content',
        'content_type',
        'external_id',
        'body',
        'media_id',
        'media_url',
        'caption',
        'reply_to_message_id',
        'quoted_message_id',
        'template_name',
        'template_language',
        'template_params',
        'context',
        'metadata',
        'errors',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'template_params' => 'array',
            'context' => 'array',
            'metadata' => 'array',
            'errors' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'direction' => MessageDirection::class,
            'message_type' => MessageType::class,
            'status' => MessageStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function statuses()
    {
        return $this->hasMany(MessageStatusModel::class, 'message_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function deliveryLogs()
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', MessageDirection::Inbound);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', MessageDirection::Outbound);
    }

    public function scopeByConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function isInbound(): bool
    {
        return $this->direction === MessageDirection::Inbound;
    }

    public function isOutbound(): bool
    {
        return $this->direction === MessageDirection::Outbound;
    }

    public function isSent(): bool
    {
        return $this->status === MessageStatus::Sent;
    }

    public function isDelivered(): bool
    {
        return $this->status === MessageStatus::Delivered;
    }

    public function isRead(): bool
    {
        return $this->status === MessageStatus::Read;
    }

    public function isFailed(): bool
    {
        return $this->status === MessageStatus::Failed;
    }
}
