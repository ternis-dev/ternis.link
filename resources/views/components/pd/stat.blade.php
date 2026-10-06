@props(['value' => null, 'label' => null, 'accent' => false])

<div {{ $attributes->merge(['class' => $accent ? 'nd-stat nd-stat-accent' : 'nd-stat']) }}>
    <div class="nd-stat-value">{{ $value }}</div>
    <div class="nd-stat-label">{{ $label }}</div>
</div>
