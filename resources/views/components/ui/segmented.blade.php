@props(['options' => [], 'active' => null, 'action' => null, 'suffix' => '', 'label' => 'Period'])

<div class="inline-flex overflow-hidden rounded-lg border border-neutral-300 dark:border-neutral-700" role="group" aria-label="{{ $label }}">
    @foreach ($options as $option)
        <button
            type="button"
            @if ($action) wire:click="{{ $action }}({{ $option }})" @endif
            @class([
                'cursor-pointer px-3.5 py-1.5 text-sm font-semibold transition-colors',
                'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => $active === $option,
                'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' => $active !== $option,
            ])
            @if ($loop->first) aria-current="{{ $active === $option ? 'true' : 'false' }}" @endif
        >{{ $option }}{{ $suffix }}</button>
    @endforeach
</div>
