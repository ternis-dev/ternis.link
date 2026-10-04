@props(['code' => '500', 'title' => 'Something went wrong'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · clicked.at</title>
    @vite(['resources/css/clicked.css'])
    <style>
        .clicked-error { max-width:36rem; margin:12vh auto; padding:3rem 2rem; text-align:center; border:1px solid #ddd6fe; border-radius:1.5rem; background:#fff }
        .clicked-error-code { color:#7c3aed; font:700 4rem/1 'Space Grotesk',sans-serif }
        .clicked-error a { color:#6d28d9 }
    </style>
</head>
<body class="min-h-screen bg-zinc-50 px-4 text-zinc-900">
    <main class="clicked-error">
        <a href="/" class="text-xl font-bold">clicked<span class="text-violet-600">.at</span></a>
        <div class="clicked-error-code mt-8">{{ $code }}</div>
        <h1 class="mt-4 text-2xl font-bold">{{ $title }}</h1>
        <p class="mt-3 text-zinc-600">{{ $slot }}</p>
        @if (trim($actions ?? '') !== '')<div class="mt-6">{{ $actions }}</div>@endif
        <p class="mt-8"><a href="/">Back to clicked.at</a></p>
    </main>
</body>
</html>
