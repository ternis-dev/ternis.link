<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ternis.link' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <header class="site-nav">
        <a href="/" class="nav-brand">ternis<span>.link</span></a>
        @auth
            <div class="nav-user">
                <img src="{{ auth()->user()->avatarUrl(34) }}" alt="{{ auth()->user()->name }}" class="user-avatar">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="badge">{{ auth()->user()->role->value ?? auth()->user()->role }}</span>
                <a href="{{ url('/dashboard') }}" class="btn btn-secondary btn-sm">Dashboard</a>
                <form method="POST" action="{{ url('/logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary">Logout</button>
                </form>
            </div>
        @else
            <div>
                <a href="{{ url('/login') }}" class="btn btn-primary btn-sm">Login with Ternis Auth</a>
            </div>
        @endauth
    </header>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
