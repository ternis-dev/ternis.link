<x-layouts.app :title="$title ?? 'Dashboard'">
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar">
            <nav>
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                    Overview
                </a>
                <a href="{{ route('dashboard.links') }}" @class(['active' => request()->routeIs('dashboard.links*')])>
                    Links
                </a>
                <a href="{{ route('dashboard.api-keys') }}" @class(['active' => request()->routeIs('dashboard.api-keys*')])>
                    API Keys
                </a>
                <a href="{{ route('dashboard.domains') }}" @class(['active' => request()->routeIs('dashboard.domains*')])>
                    Domains
                </a>
                @if (auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.*')])>
                        Admin
                    </a>
                @endif
            </nav>
        </aside>
        <section class="dashboard-content">
            {{ $slot }}
        </section>
    </div>
</x-layouts.app>
