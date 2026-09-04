<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'owner_id',
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'website',
        'logo_url',
        'timezone',
        'locale',
        'currency',
        'status',
        'branding',
        'preferences',
        'notes',
        'owner_name',
        'owner_email',
        'owner_phone',
        'tax_id',
        'registration_number',
        'industry',
        'employee_count',
        'onboarding_step',
        'onboarding_completed_at',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'preferences' => 'array',
            'onboarding_completed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Business $business) {
            if (empty($business->uuid)) {
                $business->uuid = (string) Str::uuid();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status', 'joined_at', 'last_switched_at', 'extra_permissions'])
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class);
    }

    public function membershipActivity(): HasMany
    {
        return $this->hasMany(MembershipActivity::class);
    }

    public function admins()
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    public function agents()
    {
        return $this->users()->wherePivot('role', 'agent');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(BusinessSetting::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function knowledgeBaseArticles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function aiProviderConfigs(): HasMany
    {
        return $this->hasMany(AiProviderConfig::class);
    }

    public function aiRequestLogs(): HasMany
    {
        return $this->hasMany(AiRequestLog::class);
    }

    public function whatsappPhoneNumbers(): HasMany
    {
        return $this->hasMany(WhatsappPhoneNumber::class);
    }

    public function whatsappTemplates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function handoverLogs(): HasMany
    {
        return $this->hasMany(HandoverLog::class);
    }

    public function broadcastCampaigns(): HasMany
    {
        return $this->hasMany(BroadcastCampaign::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
