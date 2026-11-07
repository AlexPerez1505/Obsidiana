{{-- Una etiqueta de pieza (QR + código + equipo). La usan la hoja de una entrada y la impresión por lote. --}}
@php
    $equipo = trim(collect([$pieza->producto?->tipo_equipo, $pieza->producto?->marca, $pieza->producto?->modelo])->filter()->implode(' '));
@endphp
<div class="etiqueta">
    <div class="qr">
        {{-- El QR lleva la liga pública de esta pieza: al escanearla con el
             teléfono abre su ficha, y una pistola lectora teclea el texto
             completo, del que el sistema saca el código. --}}
        {!! \App\Support\CodigoQr::svg($pieza->urlPublica() ?? $pieza->codigo, 150) !!}
    </div>
    <div class="txt">
        <div class="cod">{{ $pieza->codigo }}</div>
        @if ($equipo)<div class="eq">{{ $equipo }}</div>@endif
        <div class="em">{{ config('medibuy.empresa.nombre') }}</div>
    </div>
</div>
