<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#d92534">
    <title>@yield('title', 'Meine Links') · meinlink.at</title>
    <link rel="icon" href="{{ asset('favicon-meinlink-dashboard.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/meinlink-dashboard.css', 'resources/js/meinlink-dashboard.js'])
</head>
<body class="ml-body">
    <a class="skip ml-skip-link" href="#inhalt">Zum Inhalt</a>

    <x-meinlink::navbar />

    <main id="inhalt" class="ml-shell ml-main" role="main">
        @if(session('success'))
            <div class="notice ml-notice ml-notice-success" role="status">
                <div class="ml-notice-icon" aria-hidden="true">
                    <x-meinlink::icon name="check" size="18" />
                </div>
                <div class="ml-notice-body">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="notice error ml-notice ml-notice-error" role="alert">
                <div class="ml-notice-icon" aria-hidden="true">
                    <x-meinlink::icon name="alert" size="18" />
                </div>
                <div class="ml-notice-body">
                    Die Anmeldung konnte nicht abgeschlossen werden. Bitte versuche es erneut.
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="notice error ml-notice ml-notice-error" role="alert">
                <div class="ml-notice-icon" aria-hidden="true">
                    <x-meinlink::icon name="alert" size="18" />
                </div>
                <div class="ml-notice-body">
                    <strong>Bitte prüfe deine Eingaben.</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <x-meinlink::footer />
</body>
</html>
