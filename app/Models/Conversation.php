<?php

namespace App\Models;

use App\Enums\Conversation\ConversationPriority;
use App\Enums\Conversation\ConversationSource;
use App\Enums\Conversation\ConversationStatus;
use App\Models\WhatsApp\Message as WhatsAppMessage;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'customer_id',
        'assigned_to',
        'title',
        'status',
        'priority',
        'source',
        'channel',
        'channel_id',
        'handover_mode',
        'handover_reason',
        'handovered_at',
        'last_message_at',
        'last_message_id',
        'closed_at',
        'message_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'handovered_at' => 'datetime',
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
            'message_count' => 'integer',
            'metadata' => 'array',
            'status' => ConversationStatus::class,
            'priority' => ConversationPriority::class,
            'source' => ConversationSource::class,
        ];
    }

    protected $attributes = [
        'status' => 'new',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class)->whereNull('left_at');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ConversationLabel::class, 'conversation_label_conversation');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ConversationEvent::class);
    }

    public function routingHistory(): HasMany
    {
        return $this->hasMany(RoutingHistory::class);
    }

    public function handoverLogs(): HasMany
    {
        return $this->hasMany(HandoverLog::class);
    }

    public function aiRequestLogs(): HasMany
    {
        return $this->hasMany(AiRequestLog::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            ConversationStatus::New,
            ConversationStatus::Waiting,
            ConversationStatus::Assigned,
            ConversationStatus::AiProcessing,
            ConversationStatus::HumanReply,
        ]);
    }

    public function scopeByStatus($query, ConversationStatus|string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, ConversationPriority|string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhereHas('customer', function ($q) use ($term) {
                  $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
              })
              ->orWhereHas('messages', function ($q) use ($term) {
                  $q->where('content', 'like', "%{$term}%");
              });
        });
    }

    public function isActive(): bool
    {
        return $this->status instanceof ConversationStatus && $this->status->isActive();
    }

    public function assignTo(User $user, ?string $reason = null): void
    {
        $previous = $this->assigned_to;
        $this->assigned_to = $user->id;
        $this->status = ConversationStatus::Assigned;

        if ($previous === null) {
            $this->participants()->firstOrCreate([
                'user_id' => $user->id,
                'role' => 'agent',
                'joined_at' => now(),
            ]);
        }

        $this->save();

        $this->events()->create([
            'type' => 'assigned',
            'payload' => ['from' => $previous, 'to' => $user->id, 'reason' => $reason],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    public function close(?string $reason = null): void
    {
        $this->status = ConversationStatus::Closed;
        $this->closed_at = now();
        $this->save();

        $this->events()->create([
            'type' => 'closed',
            'payload' => ['reason' => $reason],
            'created_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->status = ConversationStatus::New;
        $this->closed_at = null;
        $this->save();

        $this->events()->create([
            'type' => 'reopened',
            'created_at' => now(),
        ]);
    }

    public function touchLastMessage(Message $message): void
    {
        $this->last_message_id = $message->id;
        $this->last_message_at = $message->created_at;
        $this->message_count = $this->messages()->count();
        $this->save();
    }
}
