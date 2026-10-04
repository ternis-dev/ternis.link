@props(['code' => '500', 'title' => 'Something went wrong'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · qr.href.nz</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background:#090d16; color:#f1f5f9; font-family:ui-sans-serif,system-ui,sans-serif }
        .qr-error { max-width:34rem; margin:12vh auto; padding:2.5rem; text-align:center; border:1px solid #1e293b; border-radius:1.5rem; background:#0f172a }
        .qr-error-code { color:#34d399; font-size:4rem; font-weight:800; letter-spacing:-.06em }
        .qr-error a { color:#6ee7b7 }
    </style>
</head>
<body>
    <main class="qr-error">
        <a href="/" aria-label="qr.href.nz home">qr.<strong>href</strong>.nz</a>
        <div class="qr-error-code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $slot }}</p>
        @if (trim($actions ?? '') !== '')<div>{{ $actions }}</div>@endif
        <p><a href="/new">Open the QR generator</a></p>
    </main>
</body>
</html>
