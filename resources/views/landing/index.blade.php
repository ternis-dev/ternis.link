<x-layouts.app title="ternis.link — URL Shortener & Insights">
    <div class="hero-centered">
        <h1>ternis<span>.link</span></h1>
        <p>Fast, high-performance link-shortening and insights platform with detailed analytics and referrer tracking.</p>
        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Get Started with Ternis Auth</a>
            @endauth
        </div>
    </div>
    @if (request()->attributes->get('domain_type') === 'public')
        <livewire:public.shorten-form />
    @endif
</x-layouts.app>
