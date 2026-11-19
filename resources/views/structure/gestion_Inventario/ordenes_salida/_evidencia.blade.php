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
    // TIFF, RAW, PSD… el navegador no los pinta: se ve su formato y se descargan desde el visor.
    $ext = strtolower(pathinfo((string) $evidencia->path, PATHINFO_EXTENSION));
    $seVe = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'jfif', 'heic', 'heif', ''], true);
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
        @elseif ($seVe)
            <img src="{{ $evidencia->url() }}" alt="Foto de evidencia subida por {{ $quien }}" loading="lazy" decoding="async">
        @else
            <span class="ev-mini-play" style="font-size:11px; font-weight:700;">{{ strtoupper($ext) }}</span>
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

{{-- Los estilos viajan con el cuadro: lo usan la orden y cada etapa. --}}
@once
    <style>
        .ev-item { position:relative; margin:0; min-width:0; }
        .ev-mini { position:relative; display:block; width:100%; aspect-ratio:1 / 1; padding:0; border:1px solid var(--border); border-radius:9px;
                   overflow:hidden; background:var(--surface-2); cursor:zoom-in; }
        .ev-mini img, .ev-mini video { display:block; width:100%; height:100%; object-fit:cover; pointer-events:none; background:#000; }
        .ev-mini img { background:none; }
        .ev-mini:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .ev-mini-play { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; background:rgba(0,0,0,.3); }
        .ev-mini-play svg { width:22px; height:22px; }
        .ev-pie { display:flex; flex-direction:column; margin-top:3px; font-size:11px; line-height:1.3; min-width:0; }
        .ev-quien { font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ev-cuando { color:var(--muted); }
        .ev-quitar { position:absolute; top:4px; right:4px; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center;
                     border:none; border-radius:7px; background:rgba(17,24,39,.62); color:#fff; cursor:pointer; }
        .ev-quitar:hover { background:var(--danger); }
        .ev-item.is-borrando { opacity:.45; pointer-events:none; }
    </style>
@endonce
