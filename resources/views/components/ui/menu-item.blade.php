@props([
    'href' => null,
    'detalle' => null,
    'blank' => false,
])

{{-- Opcion de <x-ui.menu>. Sin href se dibuja como boton. --}}

<{{ $href ? 'a' : 'button' }}
    @if ($href) href="{{ $href }}" @if ($blank) target="_blank" @endif
    @else type="button" @endif
    {{ $attributes->merge(['class' => 'ui-menu-item']) }} role="menuitem">

    {{ $icono ?? '' }}

    <span class="txt">
        <span class="t">{{ $slot }}</span>
        @if ($detalle)<span class="d">{{ $detalle }}</span>@endif
    </span>
</{{ $href ? 'a' : 'button' }}>
