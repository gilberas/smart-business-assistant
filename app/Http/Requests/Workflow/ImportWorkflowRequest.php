<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class ImportWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:255'],
            'execution_mode' => ['nullable', 'in:sequential,parallel'],
            'variables' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'triggers' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
        ];
    }
}
