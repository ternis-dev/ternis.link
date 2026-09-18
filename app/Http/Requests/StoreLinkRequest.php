<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['required', 'exists:domains,id'],
            'slug' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
