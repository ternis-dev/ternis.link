@extends('meinlink-dashboard.layout')
@section('title', $link ? 'Link bearbeiten' : 'Link erstellen')
@section('content')
    <a class="back" href="{{ route('meinlink-dashboard.index') }}">← Meine Links</a>
    <div class="page-heading"><div><p class="eyebrow">{{ $link ? 'DEIN LINK' : 'NEUER LINK' }}</p><h1>{{ $link ? 'Kleine Änderung.' : 'Mach es kurz.' }}</h1><p>{{ $link ? 'Die Kurzadresse bleibt gleich. Du bestimmst, wohin sie führt.' : 'Deine Zieladresse wird zu einem kurzen meinlink.at-Link.' }}</p></div></div>
    <form class="panel form-panel" method="post" action="{{ $link ? route('meinlink-dashboard.update', $link->id) : route('meinlink-dashboard.store') }}">
        @csrf @if($link) @method('PUT') @endif
        <div class="field"><label for="destination_url">Zieladresse <span class="muted">(Pflichtfeld)</span></label><input id="destination_url" name="destination_url" type="url" required maxlength="1024" value="{{ old('destination_url', $link?->destination_url) }}" placeholder="https://beispiel.at/deine-lange-adresse" aria-describedby="url-help" @error('destination_url') aria-invalid="true" @enderror><small id="url-help">Die vollständige Adresse mit https:// oder http://.</small></div>
        @unless($link)<div class="field"><label for="slug">Wunschkürzel <span class="muted">(optional)</span></label><div class="input-prefix"><span>meinlink.at/</span><input id="slug" name="slug" value="{{ old('slug') }}" maxlength="100" placeholder="mein-projekt" autocomplete="off"></div><small>Leer lassen für ein automatisch erzeugtes Kürzel. Verfügbarkeit und Mindestlänge richten sich nach deinem Tarif.</small></div>@endunless
        <div class="field"><label for="description">Beschreibung <span class="muted">(optional)</span></label><input id="description" name="description" value="{{ old('description', $link?->description) }}" maxlength="255" placeholder="Damit du den Link später wiederfindest"></div>
        <div class="field"><label for="expires_at">Gültig bis <span class="muted">(optional, {{ config('app.timezone') }})</span></label><input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', $link?->expires_at?->format('Y-m-d\TH:i')) }}"><small>Ohne Datum bleibt der Link aktiv, bis du ihn pausierst.</small></div>
        <div class="form-actions"><button class="button primary" type="submit">{{ $link ? 'Änderungen speichern' : 'Kurzlink erstellen' }}</button><a href="{{ route('meinlink-dashboard.index') }}">Abbrechen</a></div>
    </form>
@endsection
