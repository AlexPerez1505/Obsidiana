@props(['type' => 'submit', 'variant' => 'primary', 'class' => '', 'style' => ''])

@php
$variantClass = match ($variant) {
    'ghost' => 'btn--ghost',
    'danger' => 'btn--danger',
    default => '',
};
@endphp

<button type="{{ $type }}" class="btn {{ $variantClass }} {{ $class }}" style="{{ $style }}">
    {{ $slot }}
</button>
