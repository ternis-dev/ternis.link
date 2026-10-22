@props(['tone' => 'neutral', 'dot' => false])

<span {{ $attributes->merge(['class' => 'hnz-badge inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-xs font-semibold']) }}>
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
