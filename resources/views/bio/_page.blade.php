{{-- Bio page body: shared by the public page and the dashboard live preview.
     Params: $page (BioPage-ish), $buttons (live BioButtons), $subs, $preview (bool).
     In preview mode links/modal interaction is inert and hrefs are neutralized. --}}
@if($page->avatar_url)<img class="avatar" src="{{ $page->avatar_url }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endif
<h1 style="margin:12px 0 4px;font-size:24px">{{ $page->title }}</h1>
@if($page->bio)<p class="muted">{{ $page->bio }}</p>@endif
@if($subs->isNotEmpty())
<nav class="subnav" aria-label="Sub-pages">
@foreach($subs as $sub)<a href="{{ $preview ? '#' : '/' . $sub->slug }}">{{ $sub->title }}</a>@endforeach
</nav>
@endif
@foreach($buttons as $button)
@if($button->kind === 'divider')<hr style="margin:16px 0;opacity:.4">
@elseif($button->kind === 'header')<h2 style="margin:20px 0 4px;font-size:16px;opacity:.8">{{ $button->label }}</h2>
@elseif(($button->action ?? 'url') === 'modal')
<button type="button" class="btn" style="width:100%;cursor:pointer" data-bio-modal="m-{{ $button->id }}" data-bio-button="{{ $button->id }}">{{ $button->label }}@if($button->sublabel)<div class="muted">{{ $button->sublabel }}</div>@endif<span class="muted" style="font-size:11px">⧉ pop-up</span></button>
<dialog id="m-{{ $button->id }}" class="bio-modal" aria-label="{{ $button->modal_title ?? $button->label }}">
@if($button->modal_image_url)<img src="{{ $button->modal_image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" style="width:100%;border-radius:10px">@endif
<h3 style="margin:12px 0 4px;font-size:19px">{{ $button->modal_title ?? $button->label }}</h3>
@if($button->modal_body)<p class="muted" style="white-space:pre-line">{{ $button->modal_body }}</p>@endif
<form method="dialog" style="margin-top:14px"><button class="btn" style="width:100%;cursor:pointer" value="close">Close</button></form>
</dialog>
@else<a class="btn" href="{{ $preview ? '#' : '/t/' . $button->id }}" @unless($preview) rel="noopener" @endunless>{{ $button->label }}@if($button->sublabel)<div class="muted">{{ $button->sublabel }}</div>@endif</a>
@endif
@endforeach
<p class="muted" style="margin-top:32px;font-size:12px">Powered by ternis.link</p>
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
</script>
@endif
