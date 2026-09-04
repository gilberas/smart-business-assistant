<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md'],
            'type' => ['nullable', 'string', 'in:featured_image,attachment,cover_image,icon'],
            'article_id' => ['nullable', 'integer', 'exists:knowledge_articles,id'],
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'collection_id' => ['nullable', 'integer', 'exists:knowledge_collections,id'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }
}