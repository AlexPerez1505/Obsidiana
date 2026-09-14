{{--
    Menú de tres puntos de un movimiento.

    Lo usan la tabla y las tarjetas, para que las dos ofrezcan lo mismo.
    Espera $movimiento.
--}}

<div class="row-menu" data-row-menu>
    <button type="button" class="row-menu-btn" data-row-menu-toggle
            aria-haspopup="true" aria-expanded="false"
            aria-label="Acciones de {{ $movimiento->folio }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        <a href="{{ route('inventory.movimientos.show', $movimiento) }}" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            Ver detalle
        </a>

        {{-- Las etiquetas solo existen si la entrada dio de alta piezas. --}}
        @if ($movimiento->seriales()->exists())
            <a href="{{ route('inventory.movimientos.etiquetas', $movimiento) }}" target="_blank" role="menuitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 19h2v2h-2z"/></svg>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                Eliminar
            </button>
        @endif
    </div>
</div>
