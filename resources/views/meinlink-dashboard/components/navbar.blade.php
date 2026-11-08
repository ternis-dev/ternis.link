<header class="ml-navbar" role="banner">
    <div class="ml-navbar-stripe" aria-hidden="true"></div>
    <div class="ml-shell ml-navbar-inner">
        <div class="ml-navbar-brand-wrap">
            <a class="ml-brand" href="{{ route('meinlink-dashboard.index') }}" aria-label="meinlink.at Dashboard Startseite">
                <div class="ml-brand-emblem">
                    <x-meinlink::icon name="brand" size="28" />
                </div>
                <div class="ml-brand-text">
                    <span class="ml-brand-name">meinlink<span class="ml-brand-tld">.at</span></span>
                    <span class="ml-brand-badge">DASHBOARD</span>
                </div>
            </a>
        </div>

        <nav class="ml-nav" aria-label="Hauptnavigation">
            <a href="{{ route('meinlink-dashboard.index') }}"
               class="ml-nav-link {{ request()->routeIs('meinlink-dashboard.index', 'meinlink-dashboard.links', 'meinlink-dashboard.show', 'meinlink-dashboard.edit') ? 'is-active' : '' }}"
               @if(request()->routeIs('meinlink-dashboard.index', 'meinlink-dashboard.links', 'meinlink-dashboard.show', 'meinlink-dashboard.edit')) aria-current="page" @endif>
                <x-meinlink::icon name="link" size="16" />
                <span>Meine Links</span>
            </a>

            @auth
                <a href="{{ route('meinlink-dashboard.statistics') }}"
                   class="ml-nav-link {{ request()->routeIs('meinlink-dashboard.statistics', 'meinlink-dashboard.statistics.export') ? 'is-active' : '' }}"
                   @if(request()->routeIs('meinlink-dashboard.statistics', 'meinlink-dashboard.statistics.export')) aria-current="page" @endif>
                    <x-meinlink::icon name="chart" size="16" />
                    <span>Statistiken</span>
                </a>
            @endauth

            <a href="{{ route('meinlink-dashboard.help') }}"
               class="ml-nav-link {{ request()->routeIs('meinlink-dashboard.help') ? 'is-active' : '' }}"
               @if(request()->routeIs('meinlink-dashboard.help')) aria-current="page" @endif>
                <x-meinlink::icon name="help" size="16" />
                <span>Hilfe</span>
            </a>
        </nav>

        <div class="ml-navbar-actions">
            @auth
                <a class="ml-btn ml-btn-primary ml-btn-sm" href="{{ route('meinlink-dashboard.create') }}">
                    <x-meinlink::icon name="plus" size="15" />
                    <span>Neuer Link</span>
                </a>

                <form method="post" action="/logout" class="ml-logout-form">
                    @csrf
                    <button class="ml-btn ml-btn-subtle ml-btn-sm" type="submit" title="Abmelden">
                        <x-meinlink::icon name="logout" size="15" />
                        <span>Abmelden</span>
                    </button>
                </form>
            @else
                <a class="ml-btn ml-btn-primary ml-btn-sm" href="/auth/redirect">
                    <span>Anmelden</span>
                </a>
            @endauth
        </div>
    </div>
</header>
