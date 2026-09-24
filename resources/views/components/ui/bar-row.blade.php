@props(['label' => '', 'value' => '', 'share' => 0])

<div>
    <div class="mb-1.5 flex items-baseline justify-between gap-4">
        <span class="max-w-[70%] truncate text-sm" title="{{ $label }}">{{ $label }}</span>
        <span class="text-xs whitespace-nowrap text-neutral-500 dark:text-neutral-400">{{ $value }}</span>
    </div>
    <div class="h-1.5 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800" role="progressbar" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label }}">
        <div class="h-full min-w-[2px] rounded-full bg-neutral-900 dark:bg-white" style="width: {{ $share }}%;"></div>
    </div>
</div>
