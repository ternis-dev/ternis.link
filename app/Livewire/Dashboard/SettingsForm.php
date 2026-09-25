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
    }

    public function save(): void
    {
        $this->validate([
            'nav_layout' => ['required', Rule::in(User::NAV_LAYOUTS)],
            'theme' => ['required', Rule::in(User::THEMES)],
            'notify_security_email' => ['required', 'boolean'],
            'notify_admin_security_email' => ['required', 'boolean'],
            'notify_server_error_email' => ['required', 'boolean'],
        ]);

        auth()->user()->update([
            'nav_layout' => $this->nav_layout,
            'theme' => $this->theme,
            'notify_security_email' => $this->notify_security_email,
            'notify_admin_security_email' => $this->notify_admin_security_email,
            'notify_server_error_email' => $this->notify_server_error_email,
        ]);

        $this->saved = true;

        // Applies immediately in this browser (localStorage + repaint).
        $this->dispatch('tl:theme-preference', theme: $this->theme);
    }

    public function render()
    {
        return view('livewire.dashboard.settings-form');
    }
}
