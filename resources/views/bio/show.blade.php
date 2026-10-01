<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->title }}</title>
@if($draft ?? false)<meta name="robots" content="noindex">@endif
@if($og['description'])<meta name="description" content="{{ $og['description'] }}">@endif
<meta property="og:title" content="{{ $og['title'] }}">
@if($og['description'])<meta property="og:description" content="{{ $og['description'] }}">@endif
@if($og['image'])<meta property="og:image" content="{{ $og['image'] }}">@endif
<style>
body{font-family:system-ui,sans-serif;margin:0;background:{{ $page->theme === 'dark' ? '#111' : ($page->theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $page->theme === 'dark' ? '#f5f5f5' : '#171717' }}}
.wrap{max-width:480px;margin:0 auto;padding:32px 20px 64px;text-align:center}
.avatar{width:88px;height:88px;border-radius:999px;object-fit:cover}
.btn{display:block;margin:12px 0;padding:14px 16px;border-radius:14px;border:1px solid #d4d4d4;text-decoration:none;color:inherit;background:{{ $page->theme === 'dark' ? '#1c1c1c' : '#fff' }}{{ $page->accent ? ';border-color:'.$page->accent : '' }}
.subnav{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:16px 0}
.subnav a{font-size:13px;padding:6px 12px;border-radius:9999px;border:1px solid #d4d4d4;text-decoration:none;color:inherit}
.muted{opacity:.65;font-size:14px}
.bio-modal{border:1px solid #d4d4d4;border-radius:16px;padding:20px;max-width:min(420px,90vw);text-align:center}
.bio-modal::backdrop{background:rgba(0,0,0,.5)}
.draft-banner{position:sticky;top:0;z-index:10;background:#fef08a;color:#713f12;font-size:13px;padding:8px 12px;text-align:center}
</style>
</head>
<body>
@if($draft ?? false)<div class="draft-banner">Draft preview — only people with this link can see it. Not counted in analytics.</div>@endif
<div class="wrap">
@include('bio._page', ['page' => $page, 'buttons' => $buttons, 'subs' => $subs, 'preview' => false])
</div>
</body>
</html>
