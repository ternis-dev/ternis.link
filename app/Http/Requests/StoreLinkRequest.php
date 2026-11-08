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

    protected function prepareForValidation(): void
    {
        // 1. Support 'url' as alias for 'destination_url'
        if (! $this->has('destination_url') && $this->filled('url')) {
            $this->merge(['destination_url' => $this->input('url')]);
        }

        // 2. Support 'utm' nested object
        if ($this->has('utm') && is_array($this->input('utm'))) {
            $utm = $this->input('utm');
            $this->merge(array_filter([
                'utm_source' => $utm['source'] ?? $utm['utm_source'] ?? null,
                'utm_medium' => $utm['medium'] ?? $utm['utm_medium'] ?? null,
                'utm_campaign' => $utm['campaign'] ?? $utm['utm_campaign'] ?? null,
            ], fn ($v) => $v !== null));
        }

        // 3. Support comma-separated tags string
        if ($this->filled('tags') && is_string($this->input('tags'))) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $this->input('tags')))));
            $this->merge(['tags' => $tags]);
        }

        // 4. Support expires_in shorthand (e.g. '30d', '24h', '3600')
        if (! $this->has('expires_at') && $this->filled('expires_in')) {
            $parsed = $this->parseDurationToDate($this->input('expires_in'));
            if ($parsed) {
                $this->merge(['expires_at' => $parsed]);
            }
        }

        // 5. Preferred domain & domain string fallback
        if ((! $this->has('domain_id') || $this->input('domain_id') === null || $this->input('domain_id') === '')) {
            if ($this->filled('domain')) {
                $domainInput = trim((string) $this->input('domain'));
                $matched = Domain::where('hostname', $domainInput)->orWhere('id', $domainInput)->first();
                if ($matched) {
                    $this->merge(['domain_id' => $matched->id]);
                    return;
                }
            }

            /** @var \App\Models\ApiKey|null $apiKey */
            $apiKey = $this->attributes->get('api_key');
            if ($apiKey instanceof \App\Models\ApiKey) {
                $preferred = $apiKey->resolvedPreferredDomain();
                if ($preferred) {
                    $this->merge(['domain_id' => $preferred->id]);
                    return;
                }
            }

            if ($this->user()) {
                $default = $this->user()->resolvedDefaultDomain();
                if ($default) {
                    $this->merge(['domain_id' => $default->id]);
                }
            }
        }
    }

    private function parseDurationToDate(mixed $val): ?string
    {
        if (is_numeric($val)) {
            return now()->addSeconds((int) $val)->toIso8601String();
        }
        if (is_string($val)) {
            $val = trim($val);
            if (preg_match('/^(\d+)\s*(s|sec|seconds?|m|min|minutes?|h|hours?|d|days?|w|weeks?|mo|months?|y|years?)$/i', $val, $matches)) {
                $num = (int) $matches[1];
                $unit = strtolower($matches[2]);
                $dt = match ($unit) {
                    's', 'sec', 'second', 'seconds' => now()->addSeconds($num),
                    'm', 'min', 'minute', 'minutes' => now()->addMinutes($num),
                    'h', 'hour', 'hours' => now()->addHours($num),
                    'd', 'day', 'days' => now()->addDays($num),
                    'w', 'week', 'weeks' => now()->addWeeks($num),
                    'mo', 'month', 'months' => now()->addMonths($num),
                    'y', 'year', 'years' => now()->addYears($num),
                    default => null,
                };
                return $dt?->toIso8601String();
            }
            try {
                $carbon = \Carbon\Carbon::parse($val);
                return $carbon->toIso8601String();
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    public function rules(): array
    {
        $minLength = $this->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $domainId = $this->input('domain_id');

        $rules = [
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
            'description' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_campaign' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/'],
            'user_tracking_enabled' => ['nullable', 'boolean'],
            'bot_protection' => ['nullable', 'boolean'],
            'allow_bots' => ['nullable', 'boolean'],
            'health_check_enabled' => ['nullable', 'boolean'],
        ];

        // Same semantics as the dashboard form: the picker is only
        // enforced for eligible users generating (not customizing).
        // Optional here (unlike the form): existing API clients that
        // never send it keep getting the plan-minimum length.
        if ($this->canChooseSlugLength() && trim((string) $this->input('slug')) === '') {
            [$min, $max] = $this->slugLengthBounds();
            $rules['slug_length'] = ['nullable', 'integer', "min:{$min}", "max:{$max}"];
        }

        return $rules;
    }

    public function messages(): array
    {
        $minLength = $this->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $planName = $this->user()?->plan?->name ?? 'current';
        [$lengthMin, $lengthMax] = $this->slugLengthBounds();

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for your plan ({$planName}).",
            'slug.unique' => 'This slug is already taken on the selected domain.',
            'description.max' => 'The description may not exceed 500 characters.',
            'tags.*.regex' => 'Each tag may only contain lowercase letters, numbers and dashes.',
            'slug_length.integer' => 'The slug length must be a whole number.',
            'slug_length.min' => "The slug length must be at least {$lengthMin} characters.",
            'slug_length.max' => "The slug length may not exceed {$lengthMax} characters.",
        ];
    }

    public function canChooseSlugLength(): bool
    {
        return (bool) $this->user()?->canChooseSlugLength();
    }

    /**
     * @return array{int, int} [min, max]
     */
    public function slugLengthBounds(): array
    {
        if ($this->user()?->isAdmin()) {
            return [3, 64];
        }

        $planMin = $this->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;

        $max = $this->user()?->plan?->maxGeneratedSlugLength() ?? 64;

        return [max(3, $planMin), $max];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (! $user) {
                return;
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
