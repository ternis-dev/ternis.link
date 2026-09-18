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
    </div>
</x-layouts.app>
