@props(['eyebrow' => null, 'title', 'description' => null])

<div class="ml-page-header">
    <div class="ml-page-header-main">
        @if($eyebrow)
            <div class="ml-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="ml-page-title">{!! $title !!}</h1>
        @if($description)
            <p class="ml-page-desc">{{ $description }}</p>
        @endif
    </div>
    @if(isset($actions) && $actions->isNotEmpty())
        <div class="ml-page-header-actions">
            {{ $actions }}
        </div>
    @endif
</div>
