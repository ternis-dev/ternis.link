<?php

namespace App\Http\Requests;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Services\LinkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $domainId = $this->input('domain_id');
        $minLength = LinkService::ANONYMOUS_MIN_SLUG_LENGTH;

        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['nullable', 'exists:domains,id'],
            'slug' => [
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'max:255',
                "min:{$minLength}",
                Rule::unique('links', 'slug')->where(fn ($query) => $query->where('domain_id', $domainId)),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        $minLength = LinkService::ANONYMOUS_MIN_SLUG_LENGTH;

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for guest links. Log in for shorter slugs.",
            'slug.unique' => 'This slug is already taken on the selected domain.',
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
