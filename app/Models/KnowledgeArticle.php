<?php

namespace App\Models;

use Database\Factories\KnowledgeArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeArticle extends Model
{
    /** @use HasFactory<KnowledgeArticleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'article_type',
        'status',
        'visibility',
        'featured_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_featured',
        'published_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgeArticle $article) {
            if (empty($article->uuid)) {
                $article->uuid = (string) Str::uuid();
            }
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function (KnowledgeArticle $article) {
            if ($article->isDirty('title') && !$article->isDirty('slug')) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_article_tag')
            ->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class, 'knowledge_article_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(KnowledgeFaq::class, 'article_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(KnowledgeMedium::class, 'article_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeCollection::class, 'knowledge_collection_article')
            ->withTimestamps()
            ->withPivot('sort_order');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('article_type', $type);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%")
              ->orWhere('excerpt', 'like', "%{$term}%");
        });
    }

    public function scopeOrdered($query, string $sort = 'created_at', string $direction = 'desc')
    {
        return $query->orderBy($sort, $direction);
    }
}
