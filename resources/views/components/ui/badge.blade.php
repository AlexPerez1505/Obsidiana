@props(['variant' => 'default', 'style' => ''])

@php
$variantClass = match ($variant) {
    'ok' => 'badge--ok',
    'warn' => 'badge--warn',
    'danger' => 'badge--danger',
    'info' => 'badge--info',
    default => '',
};
@endphp

<span class="badge {{ $variantClass }}" style="{{ $style }}">{{ $slot }}</span>
