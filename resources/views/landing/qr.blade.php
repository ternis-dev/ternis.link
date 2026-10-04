<!DOCTYPE html>
@php($compact = $compact ?? false)
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>qr.href.nz — Free, Fast & Programmable QR Code Generator</title>
    <meta name="description" content="Generate customizable, high-resolution QR codes for URLs, Wi-Fi networks, vCards, WhatsApp, SMS, locations, and crypto. Free, privacy-first, with instant SVG/PNG exports and direct API URLs.">
    <meta name="keywords" content="QR code generator, free QR code, wifi qr code, vcard qr code, whatsapp qr code, vector qr code, svg qr code, developer qr api">
    <meta name="author" content="Ternis">
    <meta name="theme-color" content="#0a0f1d">

    <link rel="canonical" href="https://qr.href.nz/">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="qr.href.nz">
    <meta property="og:title" content="qr.href.nz — The Programmable QR Code Studio">
    <meta property="og:description" content="Generate high-resolution QR codes for all payloads: URLs, Wi-Fi, contacts, WhatsApp, events, and crypto. Free, instant SVG & PNG downloads, and developer REST API.">
    <meta property="og:url" content="https://qr.href.nz/">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --qr-emerald: #10b981;
            --qr-emerald-glow: rgba(16, 185, 129, 0.18);
            --qr-bg: #090d16;
            --qr-surface: #0f172a;
            --qr-border: #1e293b;
        }
        body {
            background-color: var(--qr-bg);
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .matrix-glow {
            box-shadow: 0 0 35px var(--qr-emerald-glow);
        }
        .scan-reticle {
            position: relative;
        }
        .scan-reticle::before, .scan-reticle::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border-color: #10b981;
            pointer-events: none;
        }
        .scan-reticle::before {
            top: -6px;
            left: -6px;
            border-top: 3px solid;
            border-left: 3px solid;
        }
        .scan-reticle::after {
            bottom: -6px;
            right: -6px;
            border-bottom: 3px solid;
            border-right: 3px solid;
        }
        .qr-compact main { padding-top: 2rem; }
        .qr-compact #qr-app { max-width: 42rem; margin-left: auto; margin-right: auto; }
        .qr-compact #qr-app > :last-child,
        .qr-compact main > section,
        .qr-compact footer { display: none; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white {{ $compact ? 'qr-compact' : '' }}">

    {{-- Top Utility Bar --}}
    <header class="border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-md sticky top-0 z-50 {{ $compact ? 'hidden' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="https://qr.href.nz/" class="flex items-center gap-2.5 text-white font-bold text-lg tracking-tight group">
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm8-2h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm2 2h2v2h-2v-2zM5 5h2v2H5V5zm12 0h2v2h-2V5zM5 17h2v2H5v-2z"/></svg>
                    </span>
                    <span>qr.<span class="text-emerald-400">href</span>.nz</span>
                </a>
                <span class="hidden sm:inline-flex text-[11px] font-mono uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Studio & API
                </span>
            </div>

            <nav class="flex items-center gap-4 text-xs font-medium text-slate-400">
                <a href="#api" class="hover:text-white transition-colors">API Docs</a>
                <a href="https://href.nz/" class="hover:text-emerald-400 transition-colors">Shortener</a>
                <a href="https://clicked.at/" class="hover:text-purple-400 transition-colors">Analytics</a>
                <a href="{{ \App\Support\DomainUrls::impressum('qr.href.nz') }}" class="hover:text-white transition-colors">Imprint</a>
            </nav>
        </div>
    </header>

    {{-- Hero & Main Application --}}
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 w-full">
        <div class="text-center max-w-3xl mx-auto mb-10 {{ $compact ? 'hidden' : '' }}">
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-3">
                Generate <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-200">Any QR Code</span> in Real Time.
            </h1>
            <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto">
                No sign-up, no expired links, no watermarks. Create vector SVGs and high-res PNGs for URLs, Wi-Fi networks, vCards, WhatsApp, and more.
            </p>
        </div>

        {{-- Generator Card Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="qr-app">

            {{-- Configuration Column (7 cols) --}}
            <div class="lg:col-span-7 bg-slate-900/70 border border-slate-800 rounded-2xl p-6 sm:p-7 backdrop-blur shadow-xl">
                
                {{-- Type Selection Tabs --}}
                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Payload Type</label>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2" id="type-pills">
                        <button type="button" data-type="url" class="type-pill active px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-emerald-500/20 border-emerald-500/40 text-emerald-300">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1-.1l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1"/></svg> URL
                        </button>
                        <button type="button" data-type="text" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16M4 12h10M4 19h16"/></svg> Text
                        </button>
                        <button type="button" data-type="wifi" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9a11 11 0 0 1 14 0M8 12a7 7 0 0 1 8 0M11 15a3 3 0 0 1 2 0M12 19h.01"/></svg> Wi-Fi
                        </button>
                        <button type="button" data-type="vcard" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg> Contact
                        </button>
                        <button type="button" data-type="email" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg> Email
                        </button>
                        <button type="button" data-type="phone" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h3l2 5-2 2a14 14 0 0 0 5 5l2-2 5 2v3c-8 2-17-7-15-15Z"/></svg> Phone
                        </button>
                        <button type="button" data-type="sms" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H8l-4 4V5Z"/></svg> SMS
                        </button>
                        <button type="button" data-type="whatsapp" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 20l1-4a8 8 0 1 1 3 3l-4 1Z"/><path d="M9 9c1 4 4 5 6 6"/></svg> WhatsApp
                        </button>
                        <button type="button" data-type="geo" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/></svg> Geo
                        </button>
                        <button type="button" data-type="event" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg> Event
                        </button>
                        <button type="button" data-type="crypto" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 4v16M13 4v16M6 7h7a3 3 0 0 1 0 6H6h8a3 3 0 0 1 0 6H6"/></svg> Crypto
                        </button>
                        <button type="button" data-type="raw" class="type-pill px-3 py-2 rounded-xl text-xs font-medium border text-center transition-all bg-slate-800/40 border-slate-700/60 text-slate-300 hover:border-slate-600">
                            <svg class="inline-block w-4 h-4 mr-1 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m13 2-9 12h7l-1 8 9-12h-7l1-8Z"/></svg> Raw
                        </button>
                    </div>
                </div>

                {{-- Dynamic Inputs Section --}}
                <div class="space-y-4 mb-7 min-h-[170px]" id="dynamic-form">
                    
                    {{-- URL Form --}}
                    <div class="type-form" id="form-url">
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Destination URL or Website</label>
                        <input type="url" id="input-url" placeholder="https://example.com/page" value="https://href.nz" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm">
                        <p class="text-[11px] text-slate-400 mt-1.5">Works with any webpage, app download link, YouTube video, or short link.</p>
                    </div>

                    {{-- Text Form --}}
                    <div class="type-form hidden" id="form-text">
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Plain Text Content</label>
                        <textarea id="input-text" rows="4" placeholder="Enter any text or notes to encode..." class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm">Welcome to qr.href.nz</textarea>
                    </div>

                    {{-- Wi-Fi Form --}}
                    <div class="type-form hidden space-y-3" id="form-wifi">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Network Name (SSID)</label>
                            <input type="text" id="wifi-ssid" placeholder="Office-Guest-WiFi" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                                <input type="text" id="wifi-password" placeholder="Passphrase" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Encryption</label>
                                <select id="wifi-encryption" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                                    <option value="WPA">WPA / WPA2 / WPA3</option>
                                    <option value="WEP">WEP</option>
                                    <option value="nopass">None (Open Network)</option>
                                </select>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-slate-400">
                            <input type="checkbox" id="wifi-hidden" class="rounded border-slate-700 text-emerald-500 focus:ring-0"> Hidden Network
                        </label>
                    </div>

                    {{-- vCard Form --}}
                    <div class="type-form hidden space-y-3" id="form-vcard">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">First Name</label>
                                <input type="text" id="vcard-fn" placeholder="Alex" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Last Name</label>
                                <input type="text" id="vcard-ln" placeholder="Smith" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Phone</label>
                                <input type="tel" id="vcard-phone" placeholder="+1234567890" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Email</label>
                                <input type="email" id="vcard-email" placeholder="alex@company.com" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Company / Organization</label>
                                <input type="text" id="vcard-org" placeholder="Ternis Dev" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Job Title</label>
                                <input type="text" id="vcard-title" placeholder="Lead Engineer" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Email Form --}}
                    <div class="type-form hidden space-y-3" id="form-email">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Recipient Email</label>
                            <input type="email" id="email-to" placeholder="contact@example.com" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Subject</label>
                            <input type="text" id="email-subject" placeholder="Inquiry" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Message Body</label>
                            <textarea id="email-body" rows="2" placeholder="Hello..." class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm"></textarea>
                        </div>
                    </div>

                    {{-- Phone Form --}}
                    <div class="type-form hidden" id="form-phone">
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Phone Number</label>
                        <input type="tel" id="input-phone" placeholder="+1234567890" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        <p class="text-[11px] text-slate-400 mt-1.5">Scanning immediately dials or prompts to call this number.</p>
                    </div>

                    {{-- SMS Form --}}
                    <div class="type-form hidden space-y-3" id="form-sms">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Phone Number</label>
                            <input type="tel" id="sms-phone" placeholder="+1234567890" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Message</label>
                            <textarea id="sms-message" rows="2" placeholder="Text message..." class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm"></textarea>
                        </div>
                    </div>

                    {{-- WhatsApp Form --}}
                    <div class="type-form hidden space-y-3" id="form-whatsapp">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">WhatsApp Phone Number (with Country Code)</label>
                            <input type="tel" id="wa-phone" placeholder="436601234567" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Pre-filled Chat Message</label>
                            <textarea id="wa-message" rows="2" placeholder="Hi, I saw your QR code..." class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm"></textarea>
                        </div>
                    </div>

                    {{-- Geo Form --}}
                    <div class="type-form hidden space-y-3" id="form-geo">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Latitude</label>
                                <input type="text" id="geo-lat" placeholder="48.2082" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Longitude</label>
                                <input type="text" id="geo-lng" placeholder="16.3738" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Place Label</label>
                            <input type="text" id="geo-label" placeholder="Vienna Center" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                    </div>

                    {{-- Event Form --}}
                    <div class="type-form hidden space-y-3" id="form-event">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Event Title</label>
                            <input type="text" id="event-title" placeholder="Product Launch" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Location</label>
                            <input type="text" id="event-location" placeholder="Convention Center or Zoom link" class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                        </div>
                    </div>

                    {{-- Crypto Form --}}
                    <div class="type-form hidden space-y-3" id="form-crypto">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Coin</label>
                                <select id="crypto-currency" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                                    <option value="bitcoin">Bitcoin (BTC)</option>
                                    <option value="ethereum">Ethereum (ETH)</option>
                                    <option value="solana">Solana (SOL)</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-300 mb-1">Wallet Address</label>
                                <input type="text" id="crypto-address" placeholder="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Raw Form --}}
                    <div class="type-form hidden" id="form-raw">
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Raw Payload</label>
                        <textarea id="input-raw" rows="4" placeholder="Raw string encoded verbatim..." class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm font-mono"></textarea>
                    </div>
                </div>

                {{-- Styling & Customization Section --}}
                <div class="border-t border-slate-800/80 pt-5 mt-5">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Styling & Customization</span>
                        <button type="button" id="btn-reset-style" class="text-[11px] text-emerald-400 hover:underline">Reset Defaults</button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        {{-- Foreground Color --}}
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1.5">Foreground</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="cfg-color" value="#000000" class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border-0 p-0">
                                <input type="text" id="cfg-color-hex" value="#000000" maxlength="7" class="w-20 px-2 py-1 bg-slate-950 border border-slate-700 rounded-lg text-xs font-mono text-white">
                            </div>
                        </div>

                        {{-- Background Color --}}
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1.5">Background</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="cfg-bg" value="#ffffff" class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border-0 p-0">
                                <input type="text" id="cfg-bg-hex" value="#ffffff" maxlength="7" class="w-20 px-2 py-1 bg-slate-950 border border-slate-700 rounded-lg text-xs font-mono text-white">
                            </div>
                        </div>

                        {{-- Size --}}
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1.5">Size: <span id="label-size">350px</span></label>
                            <input type="range" id="cfg-size" min="150" max="800" step="50" value="350" class="w-full accent-emerald-500">
                        </div>

                        {{-- Error Correction --}}
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1.5">Error Correction</label>
                            <select id="cfg-ec" class="w-full px-2 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                                <option value="L">L (7% recovery)</option>
                                <option value="M" selected>M (15% standard)</option>
                                <option value="Q">Q (25% high)</option>
                                <option value="H">H (30% best)</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Live Preview Column (5 cols) --}}
            <div class="lg:col-span-5 bg-slate-900/70 border border-slate-800 rounded-2xl p-6 sm:p-7 backdrop-blur shadow-xl lg:sticky lg:top-24">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Live Preview</span>
                    <span class="text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Active Reticle
                    </span>
                </div>

                {{-- QR Display Container --}}
                <div class="p-6 bg-slate-950 border border-slate-800 rounded-xl flex items-center justify-center min-h-[340px] mb-5 relative scan-reticle">
                    <div id="qr-preview-wrapper" class="p-3 bg-white rounded-xl shadow-md transition-all">
                        <img id="qr-preview-img" src="/url/https://href.nz?format=svg&size=350" alt="Generated QR code preview" class="w-60 h-60 object-contain block">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <button type="button" id="btn-download-svg" class="w-full py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition-colors flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download SVG
                    </button>
                    <button type="button" id="btn-download-png" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs border border-slate-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PNG
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btn-copy-url" class="flex-1 py-2 px-3 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs font-mono transition-colors truncate">
                        Copy Direct URL
                    </button>
                    <button type="button" id="btn-copy-svg" class="py-2 px-3 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs transition-colors">
                        Copy SVG
                    </button>
                </div>

                {{-- Direct URL Chip --}}
                <div class="mt-4 pt-4 border-t border-slate-800/80 text-[11px] text-slate-400 font-mono break-all" id="active-url-preview">
                    https://qr.href.nz/url/https://href.nz
                </div>
            </div>

        </div>

        @if (! $compact)
        {{-- Features & Capabilities --}}
        <section class="mt-16 sm:mt-24 pt-12 border-t border-slate-800">
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-white mb-10">Why use qr.href.nz?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">Infinite Vector Scalability</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Export pure SVG vector codes that never pixelate. Perfect for billboards, packaging, business cards, print flyers, and 4K displays.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">Zero Tracking & Privacy-First</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        No middleman redirects required unless you want them. Direct text, Wi-Fi passwords, and contacts encode right on your device with no data stored.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">Direct HTTP REST API</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Embed QR codes directly into HTML emails, Markdown, invoices, or automation bots with simple GET URLs like <code class="text-emerald-300">qr.href.nz/url/{url}</code>.
                    </p>
                </div>
            </div>
        </section>

        {{-- Direct URL & API Docs Section --}}
        <section id="api" class="mt-16 pt-12 border-t border-slate-800">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-8">
                    <span class="text-xs font-mono uppercase tracking-wider text-emerald-400">Developer Documentation</span>
                    <h2 class="text-2xl font-bold text-white mt-1">Direct URLs & Programmatic API</h2>
                    <p class="text-xs text-slate-400 mt-2">Any URL pattern on qr.href.nz immediately outputs a clean SVG or PNG image.</p>
                </div>

                <div class="space-y-4 font-mono text-xs">
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <div class="text-emerald-400 mb-1"># 1. URL QR Code (append .svg or .png)</div>
                        <div class="text-slate-300 select-all">https://qr.href.nz/url/https://example.com.svg</div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <div class="text-emerald-400 mb-1"># 2. Plain Text QR Code</div>
                        <div class="text-slate-300 select-all">https://qr.href.nz/text/Hello%20World</div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <div class="text-emerald-400 mb-1"># 3. Wi-Fi Auto-Connect QR Code</div>
                        <div class="text-slate-300 select-all">https://qr.href.nz/wifi?ssid=MyNetwork&password=SecretPass&encryption=WPA</div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <div class="text-emerald-400 mb-1"># 4. vCard Contact QR Code</div>
                        <div class="text-slate-300 select-all">https://qr.href.nz/vcard?name=John+Doe&phone=+123456789&email=john@example.com</div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <div class="text-emerald-400 mb-1"># 5. Styling query parameters (color, size, error correction)</div>
                        <div class="text-slate-300 select-all">https://qr.href.nz/url/https://example.com?color=10b981&bg=0f172a&size=500&error_correction=H&download=1</div>
                    </div>
                </div>
            </div>
        </section>
        @endif
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-800/80 bg-slate-950 py-8 mt-16 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                © {{ date('Y') }} <a href="https://ternis.dev" class="text-slate-400 hover:text-white">Ternis</a>. Part of the <a href="https://ternis.link" class="text-slate-400 hover:text-white">ternis.link</a> network.
            </div>
            <div class="flex items-center gap-4">
                <a href="https://href.nz/" class="hover:text-slate-300">href.nz</a>
                <a href="https://clicked.at/" class="hover:text-slate-300">clicked.at</a>
                <a href="https://meinlink.at/" class="hover:text-slate-300">meinlink.at</a>
                <a href="https://dash.ternis.link/" class="hover:text-slate-300">Dashboard</a>
                <a href="{{ \App\Support\DomainUrls::impressum('qr.href.nz') }}" class="hover:text-slate-300">Legal & Imprint</a>
            </div>
        </div>
    </footer>

    {{-- Interactive Client-Side Script --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        let activeType = 'url';
        const img = document.getElementById('qr-preview-img');
        const activeUrlPreview = document.getElementById('active-url-preview');

        // Color & style inputs
        const cfgColor = document.getElementById('cfg-color');
        const cfgColorHex = document.getElementById('cfg-color-hex');
        const cfgBg = document.getElementById('cfg-bg');
        const cfgBgHex = document.getElementById('cfg-bg-hex');
        const cfgSize = document.getElementById('cfg-size');
        const labelSize = document.getElementById('label-size');
        const cfgEc = document.getElementById('cfg-ec');

        // Sync color pickers
        cfgColor.addEventListener('input', (e) => {
            cfgColorHex.value = e.target.value;
            updateQr();
        });
        cfgColorHex.addEventListener('input', (e) => {
            if (/^#[0-9a-f]{6}$/i.test(e.target.value)) {
                cfgColor.value = e.target.value;
                updateQr();
            }
        });
        cfgBg.addEventListener('input', (e) => {
            cfgBgHex.value = e.target.value;
            updateQr();
        });
        cfgBgHex.addEventListener('input', (e) => {
            if (/^#[0-9a-f]{6}$/i.test(e.target.value)) {
                cfgBg.value = e.target.value;
                updateQr();
            }
        });

        cfgSize.addEventListener('input', (e) => {
            labelSize.textContent = e.target.value + 'px';
            updateQr();
        });
        cfgEc.addEventListener('change', updateQr);

        document.getElementById('btn-reset-style').addEventListener('click', () => {
            cfgColor.value = '#000000';
            cfgColorHex.value = '#000000';
            cfgBg.value = '#ffffff';
            cfgBgHex.value = '#ffffff';
            cfgSize.value = '350';
            labelSize.textContent = '350px';
            cfgEc.value = 'M';
            updateQr();
        });

        // Type Pill tabs
        const pills = document.querySelectorAll('.type-pill');
        const forms = document.querySelectorAll('.type-form');

        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                pills.forEach(p => {
                    p.classList.remove('bg-emerald-500/20', 'border-emerald-500/40', 'text-emerald-300');
                    p.classList.add('bg-slate-800/40', 'border-slate-700/60', 'text-slate-300');
                });
                pill.classList.remove('bg-slate-800/40', 'border-slate-700/60', 'text-slate-300');
                pill.classList.add('bg-emerald-500/20', 'border-emerald-500/40', 'text-emerald-300');

                activeType = pill.getAttribute('data-type');
                forms.forEach(f => f.classList.add('hidden'));

                const targetForm = document.getElementById('form-' + activeType);
                if (targetForm) targetForm.classList.remove('hidden');

                updateQr();
            });
        });

        // Listen for input changes in all form fields
        document.querySelectorAll('#dynamic-form input, #dynamic-form textarea, #dynamic-form select').forEach(el => {
            el.addEventListener('input', debounce(updateQr, 200));
        });

        function buildDirectUrl(format = 'svg', download = false) {
            const origin = `${window.location.protocol}//{{ config('domains.qr_api_host', 'qr.t-api.de') }}`;
            let path = '';
            const params = new URLSearchParams();

            const suffix = `.${format}`;
            if (cfgColorHex.value !== '#000000') params.set('color', cfgColorHex.value.replace('#', ''));
            if (cfgBgHex.value !== '#ffffff') params.set('bg', cfgBgHex.value.replace('#', ''));
            if (cfgSize.value !== '300') params.set('size', cfgSize.value);
            if (cfgEc.value !== 'M') params.set('error_correction', cfgEc.value);
            if (download) params.set('download', '1');

            const qs = params.toString() ? '?' + params.toString() : '';

            switch (activeType) {
                case 'url':
                    const u = encodeURIComponent(document.getElementById('input-url').value || 'https://href.nz');
                    return `${origin}/url/${u}${suffix}${qs}`;
                case 'text':
                    const t = encodeURIComponent(document.getElementById('input-text').value || 'Hello');
                    return `${origin}/text/${t}${suffix}${qs}`;
                case 'wifi':
                    const ssid = encodeURIComponent(document.getElementById('wifi-ssid').value || 'WiFi');
                    const pass = encodeURIComponent(document.getElementById('wifi-password').value || '');
                    const enc = document.getElementById('wifi-encryption').value;
                    const hid = document.getElementById('wifi-hidden').checked ? '&hidden=1' : '';
                    return `${origin}/wifi/${ssid}?password=${pass}&encryption=${enc}${hid}${params.toString() ? '&' + params.toString() : ''}`;
                case 'vcard':
                    const fn = encodeURIComponent(document.getElementById('vcard-fn').value || '');
                    const ln = encodeURIComponent(document.getElementById('vcard-ln').value || '');
                    const phone = encodeURIComponent(document.getElementById('vcard-phone').value || '');
                    const email = encodeURIComponent(document.getElementById('vcard-email').value || '');
                    const org = encodeURIComponent(document.getElementById('vcard-org').value || '');
                    const title = encodeURIComponent(document.getElementById('vcard-title').value || '');
                    return `${origin}/vcard?first_name=${fn}&last_name=${ln}&phone=${phone}&email=${email}&org=${org}&title=${title}${params.toString() ? '&' + params.toString() : ''}`;
                case 'email':
                    const em = encodeURIComponent(document.getElementById('email-to').value || '');
                    const sub = encodeURIComponent(document.getElementById('email-subject').value || '');
                    const body = encodeURIComponent(document.getElementById('email-body').value || '');
                    return `${origin}/email/${em}?subject=${sub}&body=${body}${params.toString() ? '&' + params.toString() : ''}`;
                case 'phone':
                    const p = encodeURIComponent(document.getElementById('input-phone').value || '+1234567890');
                    return `${origin}/phone/${p}${suffix}${qs}`;
                case 'sms':
                    const sp = encodeURIComponent(document.getElementById('sms-phone').value || '');
                    const sm = encodeURIComponent(document.getElementById('sms-message').value || '');
                    return `${origin}/sms/${sp}?message=${sm}${params.toString() ? '&' + params.toString() : ''}`;
                case 'whatsapp':
                    const wp = encodeURIComponent(document.getElementById('wa-phone').value || '');
                    const wm = encodeURIComponent(document.getElementById('wa-message').value || '');
                    return `${origin}/whatsapp/${wp}?message=${wm}${params.toString() ? '&' + params.toString() : ''}`;
                case 'geo':
                    const lat = document.getElementById('geo-lat').value || '0';
                    const lng = document.getElementById('geo-lng').value || '0';
                    const glabel = encodeURIComponent(document.getElementById('geo-label').value || '');
                    return `${origin}/geo/${lat},${lng}?label=${glabel}${params.toString() ? '&' + params.toString() : ''}`;
                case 'event':
                    const etitle = encodeURIComponent(document.getElementById('event-title').value || 'Event');
                    const eloc = encodeURIComponent(document.getElementById('event-location').value || '');
                    return `${origin}/event?title=${etitle}&location=${eloc}${params.toString() ? '&' + params.toString() : ''}`;
                case 'crypto':
                    const ccoin = document.getElementById('crypto-currency').value;
                    const caddr = encodeURIComponent(document.getElementById('crypto-address').value || '');
                    return `${origin}/crypto/${caddr}?currency=${ccoin}${params.toString() ? '&' + params.toString() : ''}`;
                case 'raw':
                    const r = encodeURIComponent(document.getElementById('input-raw').value || '');
                    return `${origin}/raw/${r}${suffix}${qs}`;
                default:
                    return `${origin}/url/https%3A%2F%2Fhref.nz${suffix}${qs}`;
            }
        }

        function updateQr() {
            const url = buildDirectUrl('svg', false);
            img.src = url;
            activeUrlPreview.textContent = url;
        }

        document.getElementById('btn-download-svg').addEventListener('click', () => {
            window.location.href = buildDirectUrl('svg', true);
        });

        document.getElementById('btn-download-png').addEventListener('click', () => {
            window.location.href = buildDirectUrl('png', true);
        });

        document.getElementById('btn-copy-url').addEventListener('click', () => {
            const url = buildDirectUrl('svg', false);
            navigator.clipboard.writeText(url).then(() => {
                const btn = document.getElementById('btn-copy-url');
                const prev = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = prev, 1800);
            });
        });

        document.getElementById('btn-copy-svg').addEventListener('click', () => {
            fetch(buildDirectUrl('svg', false))
                .then(r => r.text())
                .then(svg => {
                    navigator.clipboard.writeText(svg).then(() => {
                        const btn = document.getElementById('btn-copy-svg');
                        const prev = btn.textContent;
                        btn.textContent = 'SVG Copied!';
                        setTimeout(() => btn.textContent = prev, 1800);
                    });
                });
        });

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Initial render
        updateQr();
    });
    </script>
</body>
</html>
