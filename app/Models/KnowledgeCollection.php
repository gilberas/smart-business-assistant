<?php

namespace App\Models;

use Database\Factories\KnowledgeCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeCollection extends Model
{
    /** @use HasFactory<KnowledgeCollectionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'description',
        'cover_image',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgeCollection $collection) {
            if (empty($collection->uuid)) {
                $collection->uuid = (string) Str::uuid();
            }
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });

        static::updating(function (KnowledgeCollection $collection) {
            if ($collection->isDirty('name') && !$collection->isDirty('slug')) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'knowledge_collection_article')
            ->withTimestamps()
            ->withPivot('sort_order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
