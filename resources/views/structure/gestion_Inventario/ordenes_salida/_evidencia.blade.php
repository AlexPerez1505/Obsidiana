{{--
    Un cuadro de la evidencia (foto o video), en miniatura.

    Lo pinta la hoja de la orden y también el controlador al terminar de
    subir cada archivo, para que la lista crezca sin recargar la página.
    Al tocarlo se abre el visor compartido (partials.galeria) con toda la
    evidencia de la orden, empezando por este cuadro.

    Espera: $orden, $evidencia (con subidoPor cargado) y $borrable.
    Opcional: $indice, su lugar en el visor (fotos primero, luego videos).
    Cuando lo pinta el controlador no lo sabe; el script lo corrige al
    agregarlo.
--}}
@php
    $quien = $evidencia->subidoPor?->name ?? 'Usuario eliminado';
    $cuando = $evidencia->created_at?->format('d/m/Y H:i');
    $esVideo = $evidencia->esVideo();
@endphp
<figure class="ev-item {{ $esVideo ? 'ev-item--video' : 'ev-item--foto' }}" data-evidencia="{{ $evidencia->id }}"
        data-tipo="{{ $evidencia->tipo }}" data-url="{{ $evidencia->url() }}">
    <button type="button" class="ev-mini" data-galeria data-indice="{{ $indice ?? 0 }}" data-pie="{{ $quien }} · {{ $cuando }}"
            title="{{ $quien }} · {{ $cuando }}" aria-label="Ver {{ $esVideo ? 'video' : 'foto' }} en grande">
        @if ($esVideo)
            {{-- El primer cuadro del video sirve de miniatura; se reproduce en el visor. --}}
            <video src="{{ $evidencia->url() }}#t=0.5" muted preload="metadata" playsinline></video>
            <span class="ev-mini-play" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </span>
        @else
            <img src="{{ $evidencia->url() }}" alt="Foto de evidencia subida por {{ $quien }}" loading="lazy" decoding="async">
        @endif
    </button>

    <figcaption class="ev-pie">
        <span class="ev-quien" title="{{ $quien }}">{{ $quien }}</span>
        <span class="ev-cuando">{{ $cuando }}</span>
    </figcaption>

    @if ($borrable && auth()->user()->can('salidas.eliminar'))
        <button type="button" class="ev-quitar" data-borrar="{{ route('inventory.salidas.evidencias.destroy', [$orden, $evidencia]) }}"
                title="Quitar {{ $esVideo ? 'este video' : 'esta foto' }}" aria-label="Quitar {{ $esVideo ? 'este video' : 'esta foto' }}">
            <x-ui.icon name="x" size="12"/>
        </button>
    @endif
</figure>
