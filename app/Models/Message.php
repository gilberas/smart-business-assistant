<?php

namespace App\Models;

use App\Models\WhatsApp\Message as WhatsAppMessage;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'conversation_id',
        'business_id',
        'sender_type',
        'sender_id',
        'content',
        'content_type',
        'status',
        'direction',
        'message_type',
        'external_id',
        'from_number',
        'to_number',
        'meta_message_id',
        'media_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'metadata' => 'array',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function messageAttachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
