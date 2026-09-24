@props(['label' => null, 'hint' => null, 'name' => null])

@php
$id = $attributes->get('id', $name);
$error = ($name && isset($errors)) ? $errors->first($name) : null;
$hintId = $id ? "{$id}-hint" : null;
$errorId = $id ? "{$id}-error" : null;
$describedBy = collect([$hint && ! $error && $hintId ? $hintId : null, $error && $errorId ? $errorId : null])
    ->filter()
    ->implode(' ');

$fieldClass = $error
    ? 'border-red-500 hover:border-red-500 focus:border-red-600 focus:ring-red-600/15 dark:border-red-400 dark:hover:border-red-400 dark:focus:border-red-300 dark:focus:ring-red-400/20'
    : 'border-neutral-300 hover:border-neutral-400 focus:border-neutral-900 focus:ring-neutral-900/15 dark:border-neutral-700 dark:hover:border-neutral-600 dark:focus:border-white dark:focus:ring-white/15';
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ $label }}</label>
    @endif
    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('id')->merge(['class' => "w-full appearance-none rounded-lg border bg-white py-2.5 pr-10 pl-3.5 text-sm text-neutral-900 shadow-sm transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:bg-neutral-950 dark:text-neutral-100 {$fieldClass}"]) }}
        >{{ $slot }}</select>
        <svg class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-neutral-500 dark:text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9.5 6 6 6-6"/></svg>
    </div>
    @if ($hint && ! $error)
        <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-500" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600 dark:text-red-400" role="alert" id="{{ $errorId }}">
            <svg class="mt-px h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V13"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor" stroke="none"/></svg>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
