@php($locale = $locale ?? 'en')
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $locale === 'de' ? 'Anmelden' : 'Sign in' }} · clicked.at</title>
    @vite(['resources/css/clicked.css'])
</head>
<body class="min-h-screen bg-zinc-50 px-4 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto flex min-h-screen max-w-lg items-center justify-center">
        <section class="w-full rounded-3xl border border-violet-200 bg-white p-8 text-center shadow-xl shadow-violet-100 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none">
            <a href="/" class="text-2xl font-bold">clicked<span class="text-violet-600 dark:text-violet-400">.at</span></a>
            <p class="mt-8 text-xs font-semibold uppercase tracking-[.2em] text-violet-600">{{ $locale === 'de' ? 'Mitgliederbereich' : 'Member area' }}</p>
            <h1 class="mt-3 text-3xl font-bold">{{ $locale === 'de' ? 'Anmelden' : 'Sign in' }}</h1>
            <p class="mt-3 text-sm text-zinc-500">{{ $locale === 'de' ? 'Ein Klick, kein Passwort. Die Anmeldung läuft über Ternis Auth.' : 'One click, no password. Sign-in runs through Ternis Auth.' }}</p>
            @if (session('error'))<p class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-700" role="alert">{{ session('error') }}</p>@endif
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="mt-7 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white hover:bg-violet-500">{{ $locale === 'de' ? 'Mit Ternis Auth anmelden' : 'Sign in with Ternis Auth' }}</a>
            <p class="mt-6 text-xs text-zinc-500"><a href="/" class="text-violet-600">← {{ $locale === 'de' ? 'Zurück zu clicked.at' : 'Back to clicked.at' }}</a></p>
        </section>
    </main>
</body>
</html>
