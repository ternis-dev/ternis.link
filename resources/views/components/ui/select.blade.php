@props(['label' => null, 'hint' => null, 'name' => null])

@php
$id = $attributes->get('id', $name);
$error = $name ? $errors->first($name) : null;
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $label }}</label>
    @endif
    <div class="relative">
        <select
            id="{{ $id }}"
            {{ $attributes->except('id')->merge(['class' => 'w-full appearance-none rounded-lg border border-neutral-300 bg-white pl-3.5 pr-10 py-2.5 text-sm text-neutral-900 shadow-sm transition hover:border-neutral-400 focus:border-neutral-900 focus:ring-2 focus:ring-neutral-900/15 focus:outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:hover:border-neutral-600 dark:focus:border-white dark:focus:ring-white/15 cursor-pointer']) }}
        >{{ $slot }}</select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 dark:text-neutral-500">
            <svg class="h-4 w-4 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </div>
    </div>
    @if ($hint)
        <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $error }}</p>
    @endif
</div>
