<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class CreateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:knowledge_articles,slug,NULL,id,business_id,' . request()->user()->currentBusiness()?->id],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'article_type' => ['nullable', 'string', 'in:article,guide,faq,glossary,documentation'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'visibility' => ['nullable', 'string', 'in:public,team,private'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:knowledge_tags,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }
}