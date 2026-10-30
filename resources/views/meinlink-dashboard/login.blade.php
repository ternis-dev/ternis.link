@extends('meinlink-dashboard.layout')
@section('title', 'Anmelden')
@section('content')
<div class="login-panel panel"><p class="eyebrow">WILLKOMMEN ZURÜCK</p><h1>Deine Links.<br>Dein Überblick.</h1><p>Melde dich an, um deine meinlink.at-Links zu erstellen, zu verwalten und ihre Klickzahlen anzusehen.</p><a class="button primary" href="/auth/redirect">Mit Ternis Auth anmelden →</a><small>Du verwendest dein bestehendes Ternis-Konto. Für die Anmeldung wirst du kurz zum sicheren Anmeldedienst weitergeleitet.</small><a href="https://meinlink.at">Ohne Anmeldung einen Link kürzen</a></div>
@endsection
