@props([
    'type' => 'info',
    'title' => null,
    'role' => null,
])

@php
$roleAttr = $role ?? ($type === 'error' ? 'alert' : 'status');
$iconName = match($type) {
    'error', 'danger' => 'alert',
    'warning' => 'alert',
    'success' => 'check',
    default => 'info',
};
@endphp

<div class="ml-notice ml-notice-{{ $type }}" role="{{ $roleAttr }}">
    <div class="ml-notice-icon" aria-hidden="true">
        <x-meinlink::icon :name="$iconName" size="18" />
    </div>
    <div class="ml-notice-content">
        @if($title)
            <strong class="ml-notice-title">{{ $title }}</strong>
        @endif
        <div class="ml-notice-body">
            {{ $slot }}
        </div>
    </div>
</div>
