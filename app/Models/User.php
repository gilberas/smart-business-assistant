<?php

namespace App\Models;

use App\Contracts\AuthorizationServiceInterface;
use App\Notifications\Auth\PasswordResetNotification;
use App\Support\RoleRegistry;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'timezone',
        'locale',
        'status',
        'last_login_at',
        'last_login_ip',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->withPivot(['role', 'status', 'joined_at', 'last_switched_at', 'extra_permissions'])
            ->withTimestamps();
    }

    public function ownedBusinesses()
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    public function currentBusiness(): ?Business
    {
        return app(\App\Managers\TenantManager::class)->current();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsLoggedIn(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function canInBusiness(string $permission, ?Business $business = null): bool
    {
        return app(AuthorizationServiceInterface::class)->can($this, $permission, $business);
    }

    public function cannotInBusiness(string $permission, ?Business $business = null): bool
    {
        return !$this->canInBusiness($permission, $business);
    }

    public function hasBusinessRole(string|array $roles, ?Business $business = null): bool
    {
        return app(AuthorizationServiceInterface::class)->hasRole($this, $roles, $business);
    }

    public function businessRole(?Business $business = null): ?string
    {
        return app(AuthorizationServiceInterface::class)->role($this, $business);
    }

    public function businessPermissions(?Business $business = null): array
    {
        return app(AuthorizationServiceInterface::class)->userPermissions($this, $business);
    }

    public function isSuperAdmin(): bool
    {
        return app(AuthorizationServiceInterface::class)->isSuperAdmin($this);
    }

    public function isBusinessOwner(Business $business): bool
    {
        return $business->owner_id === $this->id;
    }

    public function isBusinessAdminOrAbove(Business $business): bool
    {
        return app(AuthorizationServiceInterface::class)->isAdminOrAbove($this, $business);
    }

    public function getEffectiveBusinessRole(Business $business): string
    {
        return app(AuthorizationServiceInterface::class)->getEffectiveRole($this, $business);
    }

    public function getPivotRoleForBusiness(Business $business): ?string
    {
        $pivot = $this->businesses()
            ->where('business_id', $business->id)
            ->first()
            ?->pivot;

        return $pivot?->role;
    }
}
