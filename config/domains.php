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
    ],

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
