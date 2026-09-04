<?php

namespace App\Http\Requests\RAG;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:1000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string', 'max:50'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'strategy' => ['nullable', 'string', 'in:keyword,fulltext,tag,category,metadata,hybrid'],
        ];
    }
}
