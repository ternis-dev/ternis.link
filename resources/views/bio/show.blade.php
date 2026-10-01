<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->title }}</title>
@if($og['description'])<meta name="description" content="{{ $og['description'] }}">@endif
<meta property="og:title" content="{{ $og['title'] }}">
@if($og['description'])<meta property="og:description" content="{{ $og['description'] }}">@endif
@if($og['image'])<meta property="og:image" content="{{ $og['image'] }}">@endif
<style>
body{font-family:system-ui,sans-serif;margin:0;background:{{ $page->theme === 'dark' ? '#111' : ($page->theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $page->theme === 'dark' ? '#f5f5f5' : '#171717' }}}
.wrap{max-width:480px;margin:0 auto;padding:32px 20px 64px;text-align:center}
.avatar{width:88px;height:88px;border-radius:9999px;object-fit:cover}
.btn{display:block;margin:12px 0;padding:14px 16px;border-radius:14px;border:1px solid #d4d4d4;text-decoration:none;color:inherit;background:{{ $page->theme === 'dark' ? '#1c1c1c' : '#fff' }}{{ $page->accent ? ';border-color:'.$page->accent : '' }}
.subnav{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:16px 0}
.subnav a{font-size:13px;padding:6px 12px;border-radius:9999px;border:1px solid #d4d4d4;text-decoration:none;color:inherit}
.muted{opacity:.65;font-size:14px}
</style>
</head>
<body>
<div class="wrap">
@if($page->avatar_url)<img class="avatar" src="{{ $page->avatar_url }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endif
<h1 style="margin:12px 0 4px;font-size:24px">{{ $page->title }}</h1>
@if($page->bio)<p class="muted">{{ $page->bio }}</p>@endif
@if($subs->isNotEmpty())
<nav class="subnav" aria-label="Sub-pages">
@foreach($subs as $sub)<a href="/{{ $sub->slug }}">{{ $sub->title }}</a>@endforeach
</nav>
@endif
@foreach($buttons as $button)
@if($button->kind === 'divider')<hr style="margin:16px 0;opacity:.4">
@elseif($button->kind === 'header')<h2 style="margin:20px 0 4px;font-size:16px;opacity:.8">{{ $button->label }}</h2>
@else<a class="btn" href="/t/{{ $button->id }}" rel="noopener">{{ $button->label }}@if($button->sublabel)<div class="muted">{{ $button->sublabel }}</div>@endif</a>
@endif
@endforeach
<p class="muted" style="margin-top:32px;font-size:12px">Powered by ternis.link</p>
</div>
</body>
</html>
