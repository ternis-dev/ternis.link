@props(['code' => '500', 'title' => 'Something went wrong'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script>
        try {
            const stored = localStorage.getItem('tl-theme');
            if (stored === 'light' || (stored !== 'dark' && matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center px-6">
    <main class="w-full max-w-md text-center">
        <div class="font-display text-7xl font-bold tracking-tight">{{ $code }}</div>
        <h1 class="mt-3 text-xl font-semibold">{{ $title }}</h1>
        <p class="mx-auto mt-2 max-w-md text-sm text-neutral-500 dark:text-neutral-400">{{ $slot }}</p>
        @if (trim($actions ?? '') !== '')
            <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
        @endif
        <p class="mt-8 text-xs text-neutral-500 dark:text-neutral-500">href.nz · href.re · ternis.link</p>
    </main>
</body>
</html>
