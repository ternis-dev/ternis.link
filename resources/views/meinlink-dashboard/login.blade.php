@extends('meinlink-dashboard.layout')
@section('title', 'Anmelden')

@section('content')
    <div class="login-panel panel ml-card ml-login-card">
        <div class="ml-login-header">
            <div class="ml-login-emblem">
                <x-meinlink::icon name="brand" size="48" />
            </div>
            <p class="eyebrow ml-eyebrow">WILLKOMMEN ZURÜCK</p>
            <h1 class="ml-login-title">Deine Links.<br>Dein Überblick.</h1>
            <p class="ml-login-desc">Melde dich an, um deine meinlink.at-Links zu erstellen, zu verwalten und ihre Klickzahlen anzusehen.</p>
        </div>

        <div class="ml-login-body">
            <a class="button primary ml-btn ml-btn-primary ml-btn-block ml-btn-lg" href="/auth/redirect">
                <span>Mit Ternis Auth anmelden →</span>
            </a>
            <small class="muted ml-login-hint">Du verwendest dein bestehendes Ternis-Konto. Für die Anmeldung wirst du kurz zum sicheren Anmeldedienst weitergeleitet.</small>

            <div class="ml-login-divider">
                <span>oder</span>
            </div>

            <a class="ml-login-guest-link" href="https://meinlink.at">
                <x-meinlink::icon name="external" size="14" />
                <span>Ohne Anmeldung einen Link kürzen</span>
            </a>
        </div>
    </div>
@endsection
