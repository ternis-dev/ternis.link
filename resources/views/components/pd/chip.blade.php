@props(['active' => false])

<span {{ $attributes->merge(['class' => 'nd-domain-chip' . ($active ? ' ring-1 ring-emerald-500' : '')]) }}>{{ $slot }}</span>
