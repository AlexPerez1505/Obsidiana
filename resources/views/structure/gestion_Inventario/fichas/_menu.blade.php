{{--
    Menú de tres puntos de una ficha. Se usa igual en la tabla y en las
    tarjetas, para que las dos vistas ofrezcan lo mismo.

    Espera $rutas (ver, descargar, editar, borrar) y $ficha.
--}}

<div class="row-menu" data-row-menu>
    <button type="button" class="row-menu-btn" data-row-menu-toggle
            aria-haspopup="true" aria-expanded="false"
            aria-label="Acciones de {{ $ficha->titulo }}">
        <x-gravityui-ellipsis-vertical />
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        @if ($rutas['ver'])
            <a href="{{ $rutas['ver'] }}" target="_blank" rel="noopener" role="menuitem">
                <x-gravityui-eye />
                Ver PDF
            </a>
            <a href="{{ $rutas['descargar'] }}" role="menuitem">
                <x-gravityui-arrow-down-to-line />
                Descargar
            </a>
        @endif

        <a href="{{ $rutas['editar'] }}" role="menuitem">
            <x-gravityui-pencil />
            Editar
        </a>

        <button type="button" class="es-danger" role="menuitem"
                data-borrar-ficha
                data-url="{{ $rutas['borrar'] }}"
                data-nombre="{{ $ficha->titulo }}">
            <x-gravityui-trash-bin />
            Eliminar
        </button>
    </div>
</div>
