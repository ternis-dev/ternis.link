<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 — Maintenance · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <main class="err-wrap">
        <div class="err-code">503</div>
        <div class="err-title">Briefly unavailable.</div>
        <p class="err-msg">We’re doing maintenance or the service is starting up. Redirects and the API will be back shortly.</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Retry home</a>
        </div>
        <div class="err-home">href.nz · href.re · ternis.link</div>
    </main>
</body>
</html>
