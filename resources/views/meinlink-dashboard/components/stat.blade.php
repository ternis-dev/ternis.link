@props([
    'value',
    'label',
    'icon' => null,
    'variant' => 'red',
    'subtext' => null,
])

<div class="ml-stat ml-stat-{{ $variant }}">
    @if($icon)
        <div class="ml-stat-icon-wrap" aria-hidden="true">
            <x-meinlink::icon :name="$icon" size="22" />
        </div>
    @endif
    <div class="ml-stat-content">
        <div class="ml-stat-value">{{ $value }}</div>
        <div class="ml-stat-label">{{ $label }}</div>
        @if($subtext)
            <div class="ml-stat-subtext">{{ $subtext }}</div>
        @endif
    </div>
</div>
