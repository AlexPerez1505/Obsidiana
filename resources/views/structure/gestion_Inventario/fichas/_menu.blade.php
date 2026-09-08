{{--
    Menú de tres puntos de una ficha. Se usa igual en la tabla y en las
    tarjetas, para que las dos vistas ofrezcan lo mismo.

    Espera $rutas (ver, descargar, editar, borrar) y $ficha.
--}}

<div class="row-menu" data-row-menu>
    <button type="button" class="row-menu-btn" data-row-menu-toggle
            aria-haspopup="true" aria-expanded="false"
            aria-label="Acciones de {{ $ficha->titulo }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        @if ($rutas['ver'])
            <a href="{{ $rutas['ver'] }}" target="_blank" rel="noopener" role="menuitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                Ver PDF
            </a>
            <a href="{{ $rutas['descargar'] }}" role="menuitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                Descargar
            </a>
        @endif

        <a href="{{ $rutas['editar'] }}" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            Editar
        </a>

        <button type="button" class="es-danger" role="menuitem"
                data-borrar-ficha
                data-url="{{ $rutas['borrar'] }}"
                data-nombre="{{ $ficha->titulo }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            Eliminar
        </button>
    </div>
</div>
