@props([
    'title',
    'description' => null,
    'icon' => 'empty-links',
])

<div class="ml-empty-state">
    <div class="ml-empty-state-graphic">
        <x-meinlink::icon :name="$icon" size="96" />
    </div>
    <h3 class="ml-empty-state-title">{{ $title }}</h3>
    @if($description)
        <p class="ml-empty-state-desc">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="ml-empty-state-actions">
            {{ $slot }}
        </div>
    @endif
</div>
