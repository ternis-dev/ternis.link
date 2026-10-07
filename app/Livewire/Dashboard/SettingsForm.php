<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsForm extends Component
{
    public string $nav_layout = 'side';

    public string $theme = 'system';

    public bool $notify_security_email = true;

    public bool $notify_admin_security_email = true;

    public bool $notify_server_error_email = true;

    public ?string $default_domain_id = null;

    /** @var list<string> */
    public array $domain_order = [];

    public bool $saved = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->nav_layout = in_array($user->nav_layout, User::NAV_LAYOUTS, true)
            ? $user->nav_layout
            : 'side';

        $this->theme = in_array($user->theme, User::THEMES, true)
            ? $user->theme
            : 'system';

        $this->notify_security_email = (bool) $user->notify_security_email;
        $this->notify_admin_security_email = (bool) $user->notify_admin_security_email;
        $this->notify_server_error_email = (bool) $user->notify_server_error_email;

        $this->default_domain_id = $user->default_domain_id ?: '';

        // Hydrate domain order from available domains
        $available = $user->availableDomains();
        $this->domain_order = $available->pluck('id')->all();
    }

    public function moveDomain(string $domainId, string $direction): void
    {
        $index = array_search($domainId, $this->domain_order, true);
        if ($index === false) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($this->domain_order)) {
            return;
        }

        $order = $this->domain_order;
        [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];
        $this->domain_order = array_values($order);
    }

    public function resetDomainOrder(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $this->domain_order = $user->availableDomains()
            ->sortBy('hostname', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('id')
            ->values()
            ->all();
    }

    public function save(): void
    {
        $user = auth()->user();
        $available = $user->availableDomains();
        $allowedIds = $available->pluck('id')->all();

        $this->validate([
            'nav_layout' => ['required', Rule::in(User::NAV_LAYOUTS)],
            'theme' => ['required', Rule::in(User::THEMES)],
            'notify_security_email' => ['required', 'boolean'],
            'notify_admin_security_email' => ['required', 'boolean'],
            'notify_server_error_email' => ['required', 'boolean'],
            'default_domain_id' => ['nullable', 'string', Rule::in(['', ...$allowedIds])],
            'domain_order' => ['nullable', 'array'],
            'domain_order.*' => ['string', Rule::in($allowedIds)],
        ]);

        $user->update([
            'nav_layout' => $this->nav_layout,
            'theme' => $this->theme,
            'notify_security_email' => $this->notify_security_email,
            'notify_admin_security_email' => $this->notify_admin_security_email,
            'notify_server_error_email' => $this->notify_server_error_email,
            'default_domain_id' => $this->default_domain_id !== '' && $this->default_domain_id !== null ? $this->default_domain_id : null,
            'domain_order' => $this->domain_order !== [] ? array_values($this->domain_order) : null,
        ]);

        $this->saved = true;

        // Applies immediately in this browser (localStorage + repaint).
        $this->dispatch('tl:theme-preference', theme: $this->theme);
        $this->dispatch('notify', message: 'Settings saved.', type: 'success');
    }

    public function render()
    {
        $user = auth()->user();
        $available = $user ? $user->availableDomains() : collect();
        $domainsById = $available->keyBy('id');

        $orderedDomains = collect($this->domain_order)
            ->map(fn ($id) => $domainsById->get($id))
            ->filter()
            ->values();

        foreach ($available as $d) {
            if (! $orderedDomains->contains('id', $d->id)) {
                $orderedDomains->push($d);
            }
        }

        return view('livewire.dashboard.settings-form', [
            'availableDomains' => $available,
            'orderedDomains' => $orderedDomains,
        ]);
    }
}
