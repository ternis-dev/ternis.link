<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Forbidden · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <main class="err-wrap">
        <div class="err-code">403</div>
        <div class="err-title">You don’t have access here.</div>
        <p class="err-msg">{{ $exception->getMessage() ?: 'This area requires a different role or login. Sign in with Ternis Auth to continue.' }}</p>
        <div class="err-actions">
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-primary">Sign in</a>
            <a href="{{ url('/') }}" class="btn btn-secondary">Back home</a>
        </div>
        <div class="err-home">href.nz · href.re · ternis.link</div>
    </main>
</body>
</html>
