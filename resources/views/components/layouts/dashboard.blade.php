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
            </nav>
        </aside>
        <section class="dashboard-content">
            {{ $slot }}
        </section>
    </div>
</x-layouts.app>
