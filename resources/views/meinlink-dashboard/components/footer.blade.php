<footer class="ml-footer" role="contentinfo">
    <div class="ml-shell ml-footer-inner">
        <div class="ml-footer-brand">
            <div class="ml-footer-logo">
                <x-meinlink::icon name="brand" size="20" />
                <span class="ml-footer-name">meinlink.at</span>
            </div>
            <p class="ml-footer-tagline">Kurze Links. Alles im Blick. Betrieben auf der Ternis-Plattform.</p>
        </div>

        <nav class="ml-footer-nav" aria-label="Rechtliches und Verweise">
            <a href="https://meinlink.at" target="_blank" rel="noopener noreferrer">
                <span>meinlink.at</span>
                <x-meinlink::icon name="external" size="13" />
            </a>
            <a href="https://ternis.link/pages/legal/privacy" target="_blank" rel="noopener noreferrer">Datenschutz (EN)</a>
            <a href="https://ternis.link/pages/legal/terms" target="_blank" rel="noopener noreferrer">Nutzungsbedingungen (EN)</a>
            <a href="{{ \App\Support\DomainUrls::impressum(config('domains.meinlink_dashboard_host'), 'de') }}">Impressum</a>
        </nav>
    </div>
    <div class="ml-shell ml-footer-bottom">
        <span class="ml-footer-copy">&copy; {{ date('Y') }} meinlink.at · Alle Rechte vorbehalten.</span>
        <span class="ml-footer-badge">
            <span class="ml-status-dot ml-status-dot-active" aria-hidden="true"></span>
            Österreichische Domainplattform
        </span>
    </div>
</footer>
