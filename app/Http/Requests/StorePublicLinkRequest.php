<?php

namespace App\Http\Requests;

use App\Enums\DomainType;
use App\Models\Domain;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Intentionally public: anonymous link creation on public domains.
        // Abuse is contained via throttle middleware + per-IP daily quota.
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['nullable', 'exists:domains,id'],
            // Guests never get custom slugs — auto-generated 8-char only.
            'slug' => ['prohibited'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.prohibited' => 'Custom slugs are for logged-in users only. Guests get an auto-generated link.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('domain_id')) {
                return;
            }

            $domain = Domain::find($this->input('domain_id'));

            if (! $domain || ! $this->isPublicSystemDomain($domain)) {
                $validator->errors()->add(
                    'domain_id',
                    'Guest links can only be created on public domains.'
                );
            } elseif (! $domain->isUsableForLinks()) {
                $validator->errors()->add('domain_id', 'The selected domain is not active.');
            }
        });
    }

    public function isPublicSystemDomain(Domain $domain): bool
    {
        return $domain->isSystemDomain() && $domain->type === DomainType::Public;
    }
}
