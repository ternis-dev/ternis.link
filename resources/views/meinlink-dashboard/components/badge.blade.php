@props([
    'status' => 'active',
    'label' => null,
    'size' => 'md',
])

@php
$normalized = strtolower(trim((string)$status));
$type = 'inactive';
$displayLabel = $label ?? $status;

if (str_contains($normalized, 'aktiv') && !str_contains($normalized, 'inaktiv')) {
    $type = 'active';
} elseif (str_contains($normalized, 'abgelaufen') || str_contains($normalized, 'expired')) {
    $type = 'expired';
} elseif (str_contains($normalized, 'gesperrt') || str_contains($normalized, 'quarantin') || str_contains($normalized, 'block')) {
    $type = 'danger';
} elseif (str_contains($normalized, 'paus') || str_contains($normalized, 'inaktiv') || str_contains($normalized, 'inactive')) {
    $type = 'inactive';
}
@endphp

<span class="ml-badge ml-badge-{{ $type }} ml-badge-{{ $size }}">
    <span class="ml-badge-dot" aria-hidden="true"></span>
    <span class="ml-badge-text">{{ $displayLabel }}</span>
</span>
