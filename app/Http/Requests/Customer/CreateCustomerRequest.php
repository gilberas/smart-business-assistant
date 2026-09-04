<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,archived,inactive,blacklisted'],
            'source' => ['nullable', 'string', 'max:50'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['nullable', 'string', 'in:individual,business'],
            'lifecycle_stage' => ['nullable', 'string', 'in:lead,prospect,active,at_risk,churned,vip'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'gender' => ['nullable', 'string', 'max:10'],
            'birthday' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_twitter' => ['nullable', 'string', 'max:255'],
            'social_linkedin' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:customer_tags,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'address' => ['nullable', 'array'],
            'address.label' => ['nullable', 'string', 'max:50'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.street2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'address.country' => ['nullable', 'string', 'max:100'],
            'address.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
