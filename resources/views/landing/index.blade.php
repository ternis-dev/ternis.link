<x-layouts.app title="ternis.link — URL Shortener & Insights">
    <div class="hero-centered">
        <h1>ternis<span>.link</span></h1>
        <p>Fast, high-performance link-shortening and insights platform with detailed analytics and referrer tracking.</p>
        <div>
            @auth
                <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Go to Dashboard</a>
            @else
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Get Started with Ternis Auth</a>
            @endauth
        </div>
        <p style="margin-top: 1.5rem; font-size: 0.9rem;">
            Public shortening lives on <strong>href.nz</strong> · official business links on <strong>href.re</strong>
        </p>
    </div>
</x-layouts.app>
