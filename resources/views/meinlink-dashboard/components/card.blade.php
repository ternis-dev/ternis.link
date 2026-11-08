@props([
    'title' => null,
    'subtitle' => null,
    'class' => '',
    'as' => 'section',
])

<{{ $as }} {{ $attributes->merge(['class' => 'ml-card ' . $class]) }}>
    @if($title || isset($actions))
        <div class="ml-card-header">
            <div class="ml-card-header-text">
                @if($title)
                    <h2 class="ml-card-title">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <span class="ml-card-subtitle">{{ $subtitle }}</span>
                @endif
            </div>
            @if(isset($actions))
                <div class="ml-card-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="ml-card-body">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="ml-card-footer">
            {{ $footer }}
        </div>
    @endif
</{{ $as }}>
