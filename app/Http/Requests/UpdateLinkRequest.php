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
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'remove_password' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/'],
            'is_active' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'targets' => ['nullable', 'array', 'max:20'],
            'targets.*.id' => ['nullable', 'string'],
            'targets.*.label' => ['nullable', 'string', 'max:60'],
            'targets.*.destination_url' => ['required_with:targets', 'url', 'max:2048'],
            'targets.*.country_codes' => ['nullable', 'array', 'max:50'],
            'targets.*.country_codes.*' => ['string', 'size:2'],
            'targets.*.device' => ['nullable', 'in:desktop,mobile,tablet'],
            'targets.*.weight' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'targets.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'targets.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tags.*.regex' => 'Each tag may only contain lowercase letters, numbers and dashes.',
        ];
    }
}
