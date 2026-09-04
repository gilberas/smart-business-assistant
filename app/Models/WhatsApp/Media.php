<?php

namespace App\Models\WhatsApp;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'business_id',
        'message_id',
        'media_id',
        'mime_type',
        'sha256',
        'file_size',
        'file_name',
        'caption',
        'local_path',
        'disk',
        'url',
        'temporary_url',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'file_size' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getSizeForHumans(): string
    {
        $bytes = $this->file_size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
