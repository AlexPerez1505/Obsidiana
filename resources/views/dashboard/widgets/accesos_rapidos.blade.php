<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 3 14h8l-1 8 10-12h-8z"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    <div class="dw-accesos">
        <a href="{{ route('commercial.clientes.create') }}" class="dw-acceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo cliente
        </a>
        <a href="{{ route('commercial.cotizaciones.create') }}" class="dw-acceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nueva cotización
        </a>
        <a href="{{ route('inventory.productos.create') }}" class="dw-acceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo producto
        </a>
        <a href="{{ route('configuracion.catalogos.index') }}" class="dw-acceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4 7.5 8 4.5 8-4.5"/></svg>
            Catálogos
        </a>

        {{-- Con más espacio, más atajos, para que no queden cuatro botones sueltos. --}}
        @if (($w['nivel'] ?? 1) >= 2)
            <a href="{{ route('commercial.cotizaciones.index') }}" class="dw-acceso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                Ver cotizaciones
            </a>
            <a href="{{ route('inventory.productos.index') }}" class="dw-acceso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Ver inventario
            </a>
        @endif

        @if (($w['nivel'] ?? 1) >= 3)
            <a href="{{ route('commercial.clientes.index') }}" class="dw-acceso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Ver clientes
            </a>
            <a href="{{ route('account') }}" class="dw-acceso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/></svg>
                Mi cuenta
            </a>
        @endif
    </div>
</div>
