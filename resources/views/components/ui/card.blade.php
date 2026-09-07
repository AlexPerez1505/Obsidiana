@props(['class' => '', 'style' => ''])

{{-- Deja pasar id, data-* y demás atributos: el componente los tragaba, así
     que ni un data-panel ni un id llegaban al HTML. --}}

<div {{ $attributes->merge(['class' => 'card '.$class, 'style' => $style]) }}>
    {{ $slot }}
</div>
