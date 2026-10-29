<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Not found — {{ $page->title }}</title>
<style>
body{font-family:system-ui,sans-serif;margin:0;background:#fff;color:#171717}
.wrap{max-width:480px;margin:0 auto;padding:64px 20px;text-align:center}
a.home{display:inline-block;margin-top:16px;padding:10px 22px;border-radius:9999px;background:#171717;color:#fff;text-decoration:none;font-size:14px}
.muted{opacity:.65;font-size:14px}
code{font-family:monospace;background:#f0f0f0;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<div class="wrap">
<div style="display:flex;justify-content:center;margin-bottom:12px">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
</div>
<h1 style="font-size:22px">Nothing here — yet.</h1>
<p class="muted"><code>{{ $slug }}</code> isn't a page on {{ $domain }}.</p>
<a class="home" href="/">← {{ $page->title }}</a>
</div>
</body>
</html>
