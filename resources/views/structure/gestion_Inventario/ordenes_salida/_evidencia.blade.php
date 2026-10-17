{{--
    Un cuadro de la galería de evidencia (foto o video).

    Lo pinta la hoja de la orden y también el controlador al terminar de
    subir cada archivo, para que la galería crezca sin recargar la página.

    Espera: $orden, $evidencia (con subidoPor cargado) y $borrable.
--}}
@php
    $quien = $evidencia->subidoPor?->name ?? 'Usuario eliminado';
    $cuando = $evidencia->created_at?->format('d/m/Y H:i');
    $esVideo = $evidencia->esVideo();
@endphp
<figure class="ev-item {{ $esVideo ? 'ev-item--video' : 'ev-item--foto' }}" data-evidencia="{{ $evidencia->id }}" data-tipo="{{ $evidencia->tipo }}">
    @if ($esVideo)
        <video src="{{ $evidencia->url() }}" controls preload="metadata" playsinline></video>
    @else
        <button type="button" class="ev-mini" data-ver="{{ $evidencia->url() }}" data-pie="{{ $quien }} · {{ $cuando }}"
                aria-label="Ver foto en grande">
            <img src="{{ $evidencia->url() }}" alt="Foto de evidencia subida por {{ $quien }}" loading="lazy" decoding="async">
        </button>
    @endif

    <figcaption class="ev-pie">
        <span class="ev-quien" title="{{ $quien }}">{{ $quien }}</span>
        <span class="ev-cuando">{{ $cuando }}</span>
    </figcaption>

    @if ($borrable)
        <button type="button" class="ev-quitar" data-borrar="{{ route('inventory.salidas.evidencias.destroy', [$orden, $evidencia]) }}"
                title="Quitar {{ $esVideo ? 'este video' : 'esta foto' }}" aria-label="Quitar {{ $esVideo ? 'este video' : 'esta foto' }}">
            <x-ui.icon name="x" size="14"/>
        </button>
    @endif
</figure>
