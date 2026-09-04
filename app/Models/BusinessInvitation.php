<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\BusinessInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInvitation extends Model
{
    /** @use HasFactory<BusinessInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'invited_by',
        'user_id',
        'email',
        'role',
        'token',
        'status',
        'message',
        'expires_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', InvitationStatus::Pending->value);
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending->value;
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => InvitationStatus::Accepted->value,
            'accepted_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => InvitationStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => InvitationStatus::Expired->value,
        ]);
    }
}
