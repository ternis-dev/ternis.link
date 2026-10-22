@props(['tone' => 'info', 'title' => null])

<div {{ $attributes->merge(['class' => 'hnz-alert rounded-2xl border px-5 py-4 text-sm']) }}>
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
</div>
