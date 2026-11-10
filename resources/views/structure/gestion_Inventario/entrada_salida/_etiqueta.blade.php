{{-- Una etiqueta de pieza para la impresora térmica (51×25 mm): QR, serie, tipo y modelo.
     La usan la hoja de una entrada y la impresión por lote, para que salgan iguales. --}}
@php
    $serie = trim((string) $pieza->no_serie);
    $tipo = trim((string) ($pieza->producto?->nombreVisible() ?: $pieza->producto?->tipo_equipo));
    $modelo = trim((string) $pieza->producto?->modelo);
@endphp
<div class="etiqueta">
    <div class="qr">
        {{-- El QR lleva la liga pública de esta pieza: al escanearla con el
             teléfono abre su ficha, y una pistola lectora teclea el texto
             completo, del que el sistema saca el código. --}}
        {!! \App\Support\CodigoQr::svg($pieza->urlPublica() ?? $pieza->codigo, 150) !!}
    </div>

    <div class="txt">
        <div class="serie {{ mb_strlen($serie) > 18 ? 'serie-larga' : '' }}">{{ $serie !== '' ? $serie : 'Sin serie' }}</div>
        @if ($tipo !== '')
            <div class="modelo {{ mb_strlen($tipo) > 24 ? 'modelo-largo' : '' }}">{{ $tipo }}</div>
        @else
            <div class="cod">{{ $pieza->codigo }}</div>
        @endif
        @if ($modelo !== '')<div class="modelo {{ mb_strlen($modelo) > 24 ? 'modelo-largo' : '' }}">{{ $modelo }}</div>@endif
        <div class="em">{{ config('medibuy.empresa.nombre') }}</div>
    </div>
</div>
