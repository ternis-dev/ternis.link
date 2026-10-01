<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->title }}</title>
@if($page->theme_color)<meta name="theme-color" content="{{ $page->theme_color }}">@endif
@if($draft ?? false)<meta name="robots" content="noindex">@endif
@if($og['description'])<meta name="description" content="{{ $og['description'] }}">@endif
<meta property="og:title" content="{{ $og['title'] }}">
@if($og['description'])<meta property="og:description" content="{{ $og['description'] }}">@endif
@if($og['image'])<meta property="og:image" content="{{ $og['image'] }}">@endif
<style>
@if(($page->theme ?? 'minimal') === 'auto')
body{font-family:system-ui,sans-serif;margin:0;background:#fff;color:#171717}
@media (prefers-color-scheme:dark){body{background:#111;color:#f5f5f5}.btn{background:#1c1c1c !important;border-color:#444 !important}.socialrow a,.subnav a,.homebtn,.sharebtn,.videofacade{border-color:#444 !important}}
@else
body{font-family:system-ui,sans-serif;margin:0;background:{{ $page->theme === 'dark' ? '#111' : ($page->theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $page->theme === 'dark' ? '#f5f5f5' : '#171717' }}}
@endif
.wrap{max-width:480px;margin:0 auto;padding:32px 20px 64px;text-align:center}
.avatar{width:88px;height:88px;border-radius:999px;object-fit:cover}
.btn{display:block;margin:12px 0;padding:14px 16px;border-radius:14px;border:1px solid #d4d4d4;text-decoration:none;color:inherit;background:{{ $page->theme === 'dark' ? '#1c1c1c' : '#fff' }}{{ $page->accent ? ';border-color:'.$page->accent : '' }}
.subnav{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:16px 0}
.subnav a{font-size:13px;padding:6px 12px;border-radius:9999px;border:1px solid #d4d4d4;text-decoration:none;color:inherit}
.homebtn{display:inline-block;margin-top:10px;font-size:13px;padding:6px 14px;border-radius:9999px;border:1px solid #d4d4d4;text-decoration:none;color:inherit}
.socialrow{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin:16px 0}
.socialrow a{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:9999px;border:1px solid #d4d4d4;color:inherit}
.sharebtn{display:inline-block;margin-top:8px;font-size:13px;padding:6px 14px;border-radius:9999px;border:1px solid #d4d4d4;background:transparent;color:inherit;cursor:pointer}
.videofacade{margin:12px 0;border-radius:14px;border:1px solid #d4d4d4;overflow:hidden}
.videofacade iframe{width:100%;aspect-ratio:16/9;border:0;display:block}
.muted{opacity:.65;font-size:14px}
.bio-modal{border:1px solid #d4d4d4;border-radius:16px;padding:20px;max-width:min(420px,90vw);text-align:center}
.bio-modal::backdrop{background:rgba(0,0,0,.5)}
.draft-banner{position:sticky;top:0;z-index:10;background:#fef08a;color:#713f12;font-size:13px;padding:8px 12px;text-align:center}
.announce{margin:0 0 12px;padding:10px 14px;border-radius:12px;background:#fef9c3;color:#713f12;font-size:14px}
.announce a{color:inherit;font-weight:600}
</style>
</head>
<body>
@if($draft ?? false)<div class="draft-banner">Draft preview — only people with this link can see it. Not counted in analytics.</div>@endif
<div class="wrap">
@include('bio._page', ['page' => $page, 'buttons' => $buttons, 'subs' => $subs, 'preview' => false])
</div>
</body>
</html>
