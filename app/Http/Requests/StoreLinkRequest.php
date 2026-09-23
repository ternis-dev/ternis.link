<?php

namespace App\Http\Requests;

use App\Models\Domain;
use App\Services\LinkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minLength = $this->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $domainId = $this->input('domain_id');

        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['required', 'exists:domains,id'],
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
        $minLength = $this->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $planName = $this->user()?->plan?->name ?? 'current';

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for your plan ({$planName}).",
            'slug.unique' => 'This slug is already taken on the selected domain.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (! $user) {
                return;
            }

            $max = $user->plan?->max_links_per_day;

            if ($max === null) {
                return;
            }

            $todayCount = $user->links()
                ->where('created_at', '>=', now()->startOfDay())
                ->count();

            if ($todayCount >= $max) {
                $planName = $user->plan?->name ?? 'current';
                $validator->errors()->add(
                    'destination_url',
                    "Daily link limit reached ({$max} per day on the {$planName} plan). Try again tomorrow."
                );
            }

            $domain = Domain::find($this->input('domain_id'));

            if ($domain && ! $domain->isUsableForLinks()) {
                $validator->errors()->add(
                    'domain_id',
                    $domain->is_active
                        ? 'The selected domain has not been verified yet. Publish the DNS TXT record, then verify it.'
                        : 'The selected domain is not active.'
                );
            }
        });
    }
}
