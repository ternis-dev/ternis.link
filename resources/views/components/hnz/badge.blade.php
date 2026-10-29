@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'neutral' => 'bg-neutral-100 text-neutral-600 dark:bg-white/10 dark:text-neutral-300',
        'purple' => 'bg-violet-50 text-violet-700 dark:bg-violet-400/10 dark:text-violet-300',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300',
        'danger' => 'bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'hnz-badge inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-xs font-semibold '.($tones[$tone] ?? $tones['neutral'])]) }} data-tone="{{ $tone }}">
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
