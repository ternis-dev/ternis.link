<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 — Session expired · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <main class="err-wrap">
        <div class="err-code">419</div>
        <div class="err-title">Session expired.</div>
        <p class="err-msg">Your session timed out or the form expired. Please reload and try again — or sign in fresh.</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Reload home</a>
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-secondary">Sign in</a>
        </div>
        <div class="err-home">href.nz · href.re · ternis.link</div>
    </main>
</body>
</html>
