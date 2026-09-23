<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 — Too many requests · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <main class="err-wrap">
        <div class="err-code">429</div>
        <div class="err-title">Slow down a little.</div>
        <p class="err-msg">Too many requests in a short time. Please wait a moment and retry — guests are limited to 10/min and 50/day.</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Back home</a>
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-secondary">Log in for higher limits</a>
        </div>
        <div class="err-home">href.nz · href.re · ternis.link</div>
    </main>
</body>
</html>
