<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsForm extends Component
{
    public string $nav_layout = 'side';

    public string $theme = 'system';

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
    }

    public function save(): void
    {
        $this->validate([
            'nav_layout' => ['required', Rule::in(User::NAV_LAYOUTS)],
            'theme' => ['required', Rule::in(User::THEMES)],
        ]);

        auth()->user()->update([
            'nav_layout' => $this->nav_layout,
            'theme' => $this->theme,
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
