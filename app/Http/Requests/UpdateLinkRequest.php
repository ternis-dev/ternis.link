<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['sometimes', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/'],
            'is_active' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'tags.*.regex' => 'Each tag may only contain lowercase letters, numbers and dashes.',
        ];
    }
}
