{{--
    Menú de tres puntos de un movimiento.

    Lo usan la tabla y las tarjetas, para que las dos ofrezcan lo mismo.
    Espera $movimiento.
--}}

<div class="row-menu" data-row-menu>
    <button type="button" class="row-menu-btn" data-row-menu-toggle
            aria-haspopup="true" aria-expanded="false"
            aria-label="Acciones de {{ $movimiento->folio }}">
        <x-gravityui-ellipsis-vertical />
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        <a href="{{ route('inventory.movimientos.show', $movimiento) }}" role="menuitem">
            <x-gravityui-eye />
            Ver detalle
        </a>

        {{-- Las etiquetas solo existen si la entrada dio de alta piezas. --}}
        @if ($movimiento->seriales()->exists())
            <a href="{{ route('inventory.movimientos.etiquetas', $movimiento) }}" target="_blank" role="menuitem">
                <x-gravityui-qr-code />
                Etiquetas QR
            </a>
        @endif

        {{-- Una salida la generó una venta: borrarla dejaría esa venta sin
             origen, así que solo se pueden eliminar entradas. --}}
        @if ($movimiento->movement_type === 'entrada')
            <button type="button" role="menuitem" class="es-danger"
                    data-eliminar-movimiento
                    data-url="{{ route('inventory.movimientos.destroy', $movimiento) }}"
                    data-folio="{{ $movimiento->folio }}">
                <x-gravityui-trash-bin />
                Eliminar
            </button>
        @endif
    </div>
</div>
