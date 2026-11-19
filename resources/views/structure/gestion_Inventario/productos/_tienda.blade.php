{{--
    Mostrar u ocultar un producto en la tienda en línea.

    Espera $producto. $como: 'menu' (renglón del menú de tres puntos),
    'boton' (botón suelto) o 'etiqueta' (solo el aviso de que sale en la web).
    El botón solo aparece para quien puede editar el producto.
--}}
@php
    $como ??= 'menu';
    $enWeb = (bool) $producto->en_tienda;
    $icono = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'
        .($enWeb ? '<path d="m4 4 16 16"/>' : '').'</svg>';
@endphp

@if ($como === 'etiqueta')
    @if ($enWeb)
        <span class="badge badge--info" title="Se muestra en la página web">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="vertical-align:-1px;"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            En la web
        </span>
    @endif
@elseif (\App\Support\EdicionPropia::puedeEditarProducto(auth()->user(), $producto))
    <form method="POST" action="{{ route('inventory.productos.tienda', $producto) }}" style="margin:0;{{ $como === 'boton' ? ' display:inline;' : '' }}"
          @if ($enWeb) data-confirm="Ya no se verá en la página web. Lo puedes volver a mostrar cuando quieras." data-confirm-title="Ocultar de la web" data-confirm-ok="Ocultar" @endif>
        @csrf
        @method('PATCH')
        <input type="hidden" name="en_tienda" value="{{ $enWeb ? 0 : 1 }}">
        @if ($como === 'boton')
            <button type="submit" class="btn btn--ghost" title="{{ $enWeb ? 'Ocultar de la página web' : 'Mostrar en la página web' }}">
                {!! $icono !!}
                {{ $enWeb ? 'Ocultar de la web' : 'Mostrar en la web' }}
            </button>
        @else
            <button type="submit" role="menuitem">
                {!! $icono !!}
                {{ $enWeb ? 'Ocultar de la web' : 'Mostrar en la web' }}
            </button>
        @endif
    </form>
@endif
