<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAnalytics') ?? false;
    }

    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'string', 'in:today,yesterday,last_7_days,last_30_days,last_90_days,this_month,last_month,all_time,custom'],
            'start' => ['required_if:preset,custom', 'nullable', 'date'],
            'end' => ['required_if:preset,custom', 'nullable', 'date', 'after_or_equal:start'],
            'group_by' => ['nullable', 'string', 'in:hour,day,week,month'],
            'status' => ['nullable', 'string'],
            'channel' => ['nullable', 'string'],
            'mode' => ['nullable', 'string', 'in:ai,human'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'campaign_id' => ['nullable', 'integer', 'exists:broadcast_campaigns,id'],
            'workflow_id' => ['nullable', 'integer', 'exists:workflows,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:customer_tags,id'],
        ];
    }
}
