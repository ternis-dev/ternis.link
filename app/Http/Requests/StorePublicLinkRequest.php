<?php

namespace App\Http\Requests;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Services\LinkService;
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
            'destination_url' => ['required', 'url', 'max:'.LinkService::PUBLIC_MAX_URL_LENGTH],
            'domain_id' => ['nullable', 'exists:domains,id'],
            // Guests never get custom slugs — auto-generated 8-char only.
            'slug' => ['prohibited'],
            'og_title' => ['prohibited'],
            'og_description' => ['prohibited'],
            'og_image_url' => ['prohibited'],
            'password' => ['prohibited'],
            'expires_at' => ['nullable', 'date', 'after:now', 'before:'.now()->addYear()->toDateTimeString()],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.prohibited' => 'Custom slugs are for logged-in users only. Guests get an auto-generated link.',
            'og_title.prohibited' => 'Social previews are for logged-in users only.',
            'og_description.prohibited' => 'Social previews are for logged-in users only.',
            'og_image_url.prohibited' => 'Social previews are for logged-in users only.',
            'password.prohibited' => 'Link passwords are for logged-in users only.',
            'expires_at.before' => 'Guest links can live for at most a year.',
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
