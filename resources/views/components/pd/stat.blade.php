@props(['value' => null, 'label' => null, 'accent' => false, 'icon' => null, 'change' => null])

<div {{ $attributes->merge(['class' => $accent ? 'nd-stat nd-stat-accent' : 'nd-stat']) }}>
    <div class="flex items-center justify-between gap-2">
        <div class="nd-stat-label">{{ $label }}</div>
        @if ($icon)
            <div class="{{ $accent ? 'text-white/80' : 'text-emerald-500/70' }}">{!! $icon !!}</div>
        @endif
    </div>
    <div class="nd-stat-value mt-2">{{ $value }}</div>
    @if ($change)
        <div class="mt-1 text-[11px] font-semibold {{ $accent ? 'text-white/85' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $change }}</div>
    @endif
</div>
