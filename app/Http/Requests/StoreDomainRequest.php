<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth + plan gating handled downstream.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('hostname'))) {
            $this->merge([
                'hostname' => rtrim(strtolower(trim($this->input('hostname'))), '.'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'hostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*\.[a-z]{2,}$/',
                'unique:domains,hostname',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hostname.regex' => 'The hostname must be a valid domain name (e.g. links.example.com).',
            'hostname.unique' => 'This hostname is already registered.',
        ];
    }
}
