@props(['active' => false])

<span {{ $attributes->merge(['class' => 'hnz-chip inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '.($active ? 'ring-2 ring-violet-500' : '')]) }}>
    {{ $slot }}
</span>
