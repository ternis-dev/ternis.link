<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain → Type Mapping
    |--------------------------------------------------------------------------
    | Maps incoming hostnames to their domain type for the ResolveDomain
    | middleware. Wildcard subdomains are supported via *.hostname patterns.
    */

    'map' => [
        // Public link shortener
        'href.nz' => 'public',
        // Public link shortener, German-language (own landing + theme)
        'meinlink.at' => 'public',
        // Newsletter & email click-tracking branded domain
        'clicked.at' => 'public',
        // Video-first public shortener & creator link accelerator
        'href.yt' => 'public',
        // Dedicated QR code generator utility
        'qr.href.nz' => 'public',
        // Official QR API
        'qr.t-api.de' => 'api',
        // Business (ternis official)
        'href.re' => 'business',
        // Ternis family/partners
        'ternis.link' => 'ternis',
        // thosted internal
        'links.thosted.de' => 'ternis',
        'short.thosted.de' => 'ternis',
        'go.thosted.de' => 'ternis',
        // Go redirects
        'go.ternis.net' => 'ternis',
        'go.ternis.dev' => 'ternis',
        'go.ternis.org' => 'ternis',
        'go.ternis.eu' => 'ternis',
        // Special-purpose
        'links.t-api.de' => 'api',
        'dash.ternis.link' => 'dashboard',
        'admin.ternis.link' => 'admin',
        'int.ternis.link' => 'ternis',
        // Developer documentation
        'docs.ternis.link' => 'docs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard Subdomain Domains
    |--------------------------------------------------------------------------
    | These root domains allow *.root subdomains to be looked up in the
    | domains table for partner/family custom subdomains.
    */

    'wildcard_roots' => [
        'ternis.link',
        'href.re',
        'href.nz',
        'meinlink.at',
        'clicked.at',
        'href.yt',
    ],

    /*
    |--------------------------------------------------------------------------
    | WWW → Apex Redirects
    |--------------------------------------------------------------------------
    | www.<host> gets a 301 to the bare apex host, handled by the
    | ResolveDomain middleware before any domain resolution runs.
    */

    'www_redirect' => [
        'ternis.link',
        'meinlink.at',
        'href.nz',
        'clicked.at',
        'href.re',
        'href.yt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical Hosts (used for absolute cross-domain links + redirects)
    |--------------------------------------------------------------------------
    | route('login') resolves against APP_URL (href.nz), which 404s for
    | auth routes pinned to dash/admin hosts. Use these hosts to build
    | absolute dashboard URLs instead, preserving the request scheme so
    | local/testing (http) and production (https) both work.
    */

    'dashboard_host' => env('DOMAIN_DASHBOARD', 'dash.ternis.link'),

    'admin_host' => env('DOMAIN_ADMIN', 'admin.ternis.link'),

    'docs_host' => env('DOMAIN_DOCS', 'docs.ternis.link'),

    'public_host' => env('DOMAIN_PUBLIC', 'href.nz'),

    'meinlink_host' => env('DOMAIN_MEINLINK', 'meinlink.at'),

    'clicked_host' => env('DOMAIN_CLICKED', 'clicked.at'),

    'yt_host' => env('DOMAIN_YT', 'href.yt'),

    'qr_host' => env('DOMAIN_QR', 'qr.href.nz'),

    'qr_api_host' => env('DOMAIN_QR_API', 'qr.t-api.de'),

    'business_host' => env('DOMAIN_BUSINESS', 'href.re'),

    'internal_host' => env('DOMAIN_INTERNAL', 'int.ternis.link'),

    /*
    |--------------------------------------------------------------------------
    | Domain Type → Required Auth
    |--------------------------------------------------------------------------
    */

    'auth_required' => [
        'ternis' => true,   // family/partner auth required
        'business' => true,   // business auth required
        'public' => false,  // anyone can access
        'api' => false,  // per-route auth
        'dashboard' => true,   // SSO login required
        'admin' => true,   // admin role required
    ],
];
