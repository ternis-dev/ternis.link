@props(['label' => null, 'hint' => null, 'name' => null])

@php
$id = $attributes->get('id', $name);
$error = $name ? $errors->first($name) : null;
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ $label }}</label>
    @endif
    <select
        id="{{ $id }}"
        {{ $attributes->except('id')->merge(['class' => 'w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-neutral-900 focus:ring-2 focus:ring-neutral-900/15 focus:outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-white dark:focus:ring-white/15']) }}
    >{{ $slot }}</select>
    @if ($hint)
        <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-500">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-neutral-900 dark:text-white" role="alert">{{ $error }}</p>
    @endif
</div>
