@props(['style' => ''])

{{-- Deja pasar id, data-* y demas atributos: el componente los tragaba y un
     getElementById sobre el titulo nunca encontraba nada. --}}

<h2 {{ $attributes->merge(['class' => 'section-title', 'style' => $style]) }}>{{ $slot }}</h2>
