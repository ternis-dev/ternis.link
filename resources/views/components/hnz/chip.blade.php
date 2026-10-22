@props(['tone' => 'violet'])

<span {{ $attributes->merge(['class' => 'hnz-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold']) }}>
    {{ $slot }}
</span>
