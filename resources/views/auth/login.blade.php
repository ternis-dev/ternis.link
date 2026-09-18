<x-layouts.app title="Login — ternis.link">
    <div class="hero-centered">
        <h1>Sign in to ternis.link</h1>
        <p>Authenticate with your Ternis account to manage links, create custom short links, and view analytics.</p>

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <a href="{{ route('auth.redirect') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
            Login with Ternis Auth SSO
        </a>

        @if (app()->environment('local', 'testing'))
            <div class="card" style="margin-top: 2.5rem; width: 100%; max-width: 480px; text-align: left; background-color: rgba(30, 41, 59, 0.7); border: 1px dashed var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
                        🛠 Local Dev Demo Login
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">temporary</span>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Bypass live SSO locally to test role-based dashboards and capabilities:
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <a href="{{ route('auth.demo', ['role' => 'admin']) }}" class="btn btn-secondary btn-sm">Admin</a>
                    <a href="{{ route('auth.demo', ['role' => 'family']) }}" class="btn btn-secondary btn-sm">Family</a>
                    <a href="{{ route('auth.demo', ['role' => 'partner']) }}" class="btn btn-secondary btn-sm">Partner</a>
                    <a href="{{ route('auth.demo', ['role' => 'user']) }}" class="btn btn-secondary btn-sm">Standard User</a>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
