<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:30'],
            'type' => ['required', Rule::in(['text', 'image', 'audio', 'video', 'document', 'template', 'button', 'list', 'location'])],
            'body' => ['required_if:type,text', 'string', 'nullable'],
            'media_id' => ['required_if:type,image,audio,video,document', 'string', 'nullable'],
            'media_url' => ['nullable', 'string', 'url'],
            'caption' => ['nullable', 'string', 'max:1024'],
            'template_name' => ['required_if:type,template', 'string', 'nullable'],
            'template_language' => ['required_if:type,template', 'string', 'max:10', 'nullable'],
            'template_params' => ['nullable', 'array'],
            'reply_to_message_id' => ['nullable', 'string'],
            'latitude' => ['required_if:type,location', 'numeric', 'nullable'],
            'longitude' => ['required_if:type,location', 'numeric', 'nullable'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'interactive_buttons' => ['nullable', 'array'],
            'interactive_buttons.*.id' => ['required_with:interactive_buttons', 'string'],
            'interactive_buttons.*.title' => ['required_with:interactive_buttons', 'string', 'max:20'],
            'list_header' => ['nullable', 'string', 'max:60'],
            'list_body' => ['nullable', 'string', 'max:1024'],
            'list_footer' => ['nullable', 'string', 'max:60'],
            'list_button_text' => ['nullable', 'string', 'max:20'],
            'list_sections' => ['nullable', 'array'],
        ];
    }
}
