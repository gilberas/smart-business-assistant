<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'language',
        'category',
        'status',
        'body',
        'header',
        'footer',
        'buttons',
        'meta_template_id',
    ];

    protected function casts(): array
    {
        return [
            'header' => 'array',
            'buttons' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function broadcastCampaigns(): HasMany
    {
        return $this->hasMany(BroadcastCampaign::class, 'template_id');
    }
}
