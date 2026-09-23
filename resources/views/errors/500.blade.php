<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Something broke · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <main class="err-wrap">
        <div class="err-code">500</div>
        <div class="err-title">Something broke on our side.</div>
        <p class="err-msg">An unexpected error occurred. The team has been notified via logs — please try again in a moment.</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Back home</a>
            <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="btn btn-secondary">Dashboard</a>
        </div>
        <div class="err-home">href.nz · href.re · ternis.link</div>
    </main>
</body>
</html>
