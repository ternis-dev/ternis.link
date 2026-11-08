<?php

namespace App\Http\Requests;

use App\Services\LinkService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('destination_url') && $this->filled('url')) {
            $this->merge(['destination_url' => $this->input('url')]);
        }

        if ($this->has('utm') && is_array($this->input('utm'))) {
            $utm = $this->input('utm');
            $this->merge(array_filter([
                'utm_source' => $utm['source'] ?? $utm['utm_source'] ?? null,
                'utm_medium' => $utm['medium'] ?? $utm['utm_medium'] ?? null,
                'utm_campaign' => $utm['campaign'] ?? $utm['utm_campaign'] ?? null,
            ], fn ($v) => $v !== null));
        }

        if ($this->filled('tags') && is_string($this->input('tags'))) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $this->input('tags')))));
            $this->merge(['tags' => $tags]);
        }

        if (! $this->has('expires_at') && $this->filled('expires_in')) {
            $parsed = $this->parseDurationToDate($this->input('expires_in'));
            if ($parsed) {
                $this->merge(['expires_at' => $parsed]);
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
        return [
            'destination_url' => ['sometimes', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'remove_password' => ['nullable', 'boolean'],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_campaign' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/'],
            'user_tracking_enabled' => ['nullable', 'boolean'],
            'bot_protection' => ['nullable', 'boolean'],
            'allow_bots' => ['nullable', 'boolean'],
            'health_check_enabled' => ['nullable', 'boolean'],
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
