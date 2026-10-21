@props([
    'label' => null,
    'description' => null,
    'hint' => null,
    'name' => null,
])

@php
$id = $attributes->get('id', $name ?? 'chk-'.\Illuminate\Support\Str::random(8));
$error = $name ? $errors->first($name) : null;
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    <div class="flex h-5 items-center">
        <input
            id="{{ $id }}"
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->except(['class', 'id'])->merge([
                'class' => 'h-4 w-4 cursor-pointer rounded border border-neutral-300 bg-white text-neutral-900 shadow-xs transition duration-150 ease-in-out focus:ring-2 focus:ring-neutral-900/15 focus:ring-offset-0 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:checked:bg-white dark:checked:border-white dark:focus:ring-white/20 dark:focus:ring-offset-0 accent-neutral-900 dark:accent-white'
            ]) }}
        >
    </div>
    @if ($label || $description || $hint || $slot->isNotEmpty())
        <div class="text-sm select-none">
            @if ($label)
                <label for="{{ $id }}" class="cursor-pointer font-medium text-neutral-800 transition-colors hover:text-neutral-900 dark:text-neutral-200 dark:hover:text-white">
                    {{ $label }}
                </label>
            @endif
            @if ($slot->isNotEmpty())
                <label for="{{ $id }}" class="cursor-pointer text-neutral-700 transition-colors hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white">
                    {{ $slot }}
                </label>
            @endif
            @if ($description)
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                    {{ $description }}
                </p>
            @endif
            @if ($hint)
                <p class="mt-0.5 text-xs text-neutral-400 dark:text-neutral-500">
                    {{ $hint }}
                </p>
            @endif
            @if ($error)
                <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert">
                    {{ $error }}
                </p>
            @endif
        </div>
    @endif
</div>
