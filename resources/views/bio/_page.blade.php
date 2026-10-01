{{-- Bio page body: shared by the public page and the dashboard live preview.
     Params: $page (BioPage-ish), $buttons (live BioButtons), $subs, $preview (bool).
     In preview mode links/modal interaction is inert and hrefs are neutralized. --}}
@php
$style = $page->button_style ?? 'filled';
$btnExtra = ($page->theme ?? 'minimal') === 'auto' ? '' : ($style === 'outline'
    ? ';background:transparent'
    : ($style === 'soft' ? ';background:#f0f0f0;border-color:transparent' : ''));
$socialButtons = $buttons->filter(fn ($b) => $b->kind === 'social')->values();
$flowButtons = $buttons->filter(fn ($b) => $b->kind !== 'social')->values();
$icons = [
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>',
    'tiktok' => '<path d="M9 8v8.5a3.5 3.5 0 1 0 3.5-3.5M9 8V4.5c.8 2.3 2.6 3.5 5 3.5"/>',
    'x' => '<path d="M4 4l16 16M20 4L4 20"/>',
    'youtube' => '<rect x="2.5" y="6" width="19" height="12" rx="4"/><path d="M10.5 9.8v4.4L14.5 12z" fill="currentColor" stroke="none"/>',
    'github' => '<path d="M12 3a9 9 0 1 0 2.8 17.5c.4.1.6-.2.6-.4v-1.5c-2.5.5-3-1-3-1-.4-1-1-1.3-1-1.3-.8-.6.1-.6.1-.6.9.1 1.4 1 1.4 1 .8 1.4 2.2 1 2.7.8.1-.7.3-1.2.6-1.5-2-.2-4.1-1-4.1-4.5 0-1 .3-1.8.9-2.4-.1-.2-.4-1.1.1-2.4 0 0 .7-.2 2.4.9a8 8 0 0 1 4.4 0c1.7-1.1 2.4-.9 2.4-.9.5 1.3.2 2.2.1 2.4.6.6.9 1.4.9 2.4 0 3.5-2.1 4.3-4.1 4.5.4.3.7.9.7 1.9v2.8c0 .2.2.5.6.4A9 9 0 0 0 12 3z"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.9 5.7 3.9 9s-1.4 6.4-3.9 9c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3z"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3.5 7l8.5 6 8.5-6"/>',
    'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1-1"/>',
];
@endphp
@if($page->announcement_text)
<div class="announce" role="note">@if($page->announcement_url)<a href="{{ $preview ? '#' : $page->announcement_url }}" @unless($preview) target="_blank" rel="noopener" @endunless>{{ $page->announcement_text }} →</a>@else{{ $page->announcement_text }}@endif</div>
@endif
@if($page->cover_url)<img src="{{ $page->cover_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:100%;height:140px;border-radius:16px;object-fit:cover">@endif
@if($page->avatar_url)<img class="avatar" src="{{ $page->avatar_url }}" alt="" loading="lazy" referrerpolicy="no-referrer"@if($page->cover_url) style="margin-top:-44px;border:4px solid #fff"@endif>@endif
<h1 style="margin:12px 0 4px;font-size:24px">{{ $page->title }}</h1>
@if($page->bio)<p class="muted">{{ $page->bio }}</p>@endif
@if(($page->parent_id ?? null) !== null && isset($root) && $root)
<a href="{{ $preview ? '#' : '/' }}" aria-label="Back to {{ $root->title }}" style="display:inline-block;margin-top:10px;font-size:13px;padding:6px 14px;border-radius:9999px;border:1px solid #d4d4d4;text-decoration:none;color:inherit">← {{ $root->title }}</a>
@endif
@if($subs->isNotEmpty())
<nav class="subnav" aria-label="Sub-pages">
@foreach($subs as $sub)<a href="{{ $preview ? '#' : '/' . $sub->slug }}">{{ $sub->title }}</a>@endforeach
</nav>
@endif
@if($socialButtons->isNotEmpty())
<div class="socialrow" aria-label="Social links">
@foreach($socialButtons as $button)
<a href="{{ $preview ? '#' : '/t/' . $button->id }}" @if(!$preview && ($button->open_new ?? false)) target="_blank" @endif aria-label="{{ $button->label }}" title="{{ $button->label }}"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$button->icon] ?? $icons['link'] !!}</svg></a>
@endforeach
</div>
@endif
<div @if(($page->layout ?? 'list') === 'grid') style="display:grid;grid-template-columns:1fr 1fr;gap:0 12px;align-items:start" @endif>
@foreach($flowButtons as $button)
@if($button->kind === 'divider')<hr style="margin:16px 0;opacity:.4;grid-column:1/-1">
@elseif($button->kind === 'header')<h2 style="margin:20px 0 4px;font-size:16px;opacity:.8;grid-column:1/-1">{{ $button->label }}</h2>
@elseif($button->kind === 'countdown' && $button->event_at)<div class="countdown" data-countdown="{{ $button->event_at->toIso8601String() }}" style="margin:12px 0;padding:14px;border-radius:14px;border:1px dashed #a3a3a3"><div style="font-weight:700">{{ $button->label }}</div><div class="muted" data-countdown-label>…</div></div>
@elseif($button->kind === 'image' && $button->thumbnail_url)<figure style="margin:12px 0"><img src="{{ $button->thumbnail_url }}" alt="{{ $button->label }}" loading="lazy" referrerpolicy="no-referrer" style="width:100%;border-radius:14px;display:block">@if($button->label)<figcaption class="muted" style="margin-top:6px;font-size:13px">{{ $button->label }}</figcaption>@endif</figure>
@elseif($button->kind === 'video' && ($embed = \App\Support\BioVideo::embed((string) $button->destination_url)))
<div class="videofacade" data-video="{{ $button->id }}" data-src="{{ $embed['embed'] }}" data-bio-button="{{ $button->id }}" role="button" tabindex="0" aria-label="Play video: {{ $button->label }}" @unless($preview) style="cursor:pointer" @endunless>
@if($button->thumbnail_url)<img src="{{ $button->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:100%;border-radius:14px;display:block">@endif
<div style="padding:10px 4px 2px;font-weight:600">▶ {{ $button->label }}</div>
</div>
@elseif(($button->action ?? 'url') === 'modal')
<button type="button" class="btn" style="width:100%;cursor:pointer{{ $btnExtra }}" data-bio-modal="m-{{ $button->id }}" data-bio-button="{{ $button->id }}">@if($button->thumbnail_url)<img src="{{ $button->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:44px;height:44px;border-radius:10px;object-fit:cover;vertical-align:middle;margin-right:10px">@endif{{ $button->label }}@if($button->sublabel)<div class="muted">{{ $button->sublabel }}</div>@endif<span class="muted" style="font-size:11px">⧉ pop-up</span></button>
<dialog id="m-{{ $button->id }}" class="bio-modal" aria-label="{{ $button->modal_title ?? $button->label }}">
@if($button->modal_image_url)<img src="{{ $button->modal_image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:100%;border-radius:10px">@endif
<h3 style="margin:12px 0 4px;font-size:19px">{{ $button->modal_title ?? $button->label }}</h3>
@if($button->modal_body)<p class="muted" style="white-space:pre-line">{{ $button->modal_body }}</p>@endif
<form method="dialog" style="margin-top:14px"><button class="btn" style="width:100%;cursor:pointer" value="close">Close</button></form>
</dialog>
@else<a class="btn" style="{{ ltrim($btnExtra, ';') }}" href="{{ $preview ? '#' : '/t/' . $button->id }}" @unless($preview) rel="noopener" @endunless @if(!$preview && ($button->open_new ?? false)) target="_blank" @endif>@if($button->thumbnail_url)<img src="{{ $button->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:44px;height:44px;border-radius:10px;object-fit:cover;vertical-align:middle;margin-right:10px">@endif<span style="vertical-align:middle">@if($button->kind === 'contact')⤓ @endif{{ $button->label }}@if(!empty($button->badge))<span style="display:inline-block;margin-left:8px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:9999px;background:#171717;color:#fff;vertical-align:middle">{{ $button->badge }}</span>@endif@if($button->sublabel)<div class="muted">{{ $button->sublabel }}</div>@endif</span></a>
@endif
@endforeach
</div>
@if(! $preview)
<button type="button" class="sharebtn" data-share data-title="{{ $page->title }}">⇪ Share</button>
@endif
@if(empty($page->footer_text) && !empty($page->hide_branding))
{{-- Attribution removed: premium perk. --}}
@else
<p class="muted" style="margin-top:32px;font-size:12px">{{ $page->footer_text ?? 'Powered by ternis.link' }}</p>
@endif
@if(! $preview)
<script>
document.querySelectorAll('[data-bio-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var dialog = document.getElementById(btn.getAttribute('data-bio-modal'));
        if (dialog && dialog.showModal) {
            dialog.showModal();
            new Image().src = '/t/' + btn.getAttribute('data-bio-button') + '/open.gif';
        }
    });
});
var shareBtn = document.querySelector('[data-share]');
if (shareBtn) {
    shareBtn.addEventListener('click', function () {
        var data = { title: shareBtn.getAttribute('data-title'), url: location.href };
        if (navigator.share) { navigator.share(data).catch(function () {}); }
        else if (navigator.clipboard) {
            navigator.clipboard.writeText(location.href).then(function () {
                shareBtn.textContent = 'Copied!';
                setTimeout(function () { shareBtn.textContent = '⇪ Share'; }, 2000);
            });
        }
    });
}
document.querySelectorAll('[data-video]').forEach(function (facade) {
    function play() {
        if (facade.querySelector('iframe')) { return; }
        var frame = document.createElement('iframe');
        frame.src = facade.getAttribute('data-src');
        frame.allow = 'accelerometer; encrypted-media; picture-in-picture';
        frame.allowFullscreen = true;
        frame.title = facade.getAttribute('aria-label') || 'Embedded video';
        facade.innerHTML = '';
        facade.appendChild(frame);
        new Image().src = '/t/' + facade.getAttribute('data-bio-button') + '/open.gif';
    }
    facade.addEventListener('click', play);
    facade.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); play(); }
    });
});
document.querySelectorAll('[data-countdown]').forEach(function (box) {
    var label = box.querySelector('[data-countdown-label]');
    var target = new Date(box.getAttribute('data-countdown')).getTime();
    function tick() {
        var diff = target - Date.now();
        if (diff <= 0) {
            if (label) { label.textContent = '● Live now'; }
            return;
        }
        var d = Math.floor(diff / 86400000);
        var h = Math.floor(diff % 86400000 / 3600000);
        var m = Math.floor(diff % 3600000 / 60000);
        var s = Math.floor(diff % 60000 / 1000);
        if (label) { label.textContent = (d > 0 ? d + 'd ' : '') + h + 'h ' + m + 'm ' + s + 's'; }
        setTimeout(tick, 1000);
    }
    tick();
});
</script>
@endif
