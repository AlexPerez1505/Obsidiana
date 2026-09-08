<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <x-gravityui-thunderbolt />
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    <div class="dw-accesos">
        <a href="{{ route('commercial.clientes.create') }}" class="dw-acceso">
            <x-gravityui-plus />
            Nuevo cliente
        </a>
        <a href="{{ route('commercial.cotizaciones.create') }}" class="dw-acceso">
            <x-gravityui-plus />
            Nueva cotización
        </a>
        <a href="{{ route('inventory.productos.create') }}" class="dw-acceso">
            <x-gravityui-plus />
            Nuevo producto
        </a>
        <a href="{{ route('configuracion.catalogos.index') }}" class="dw-acceso">
            <x-gravityui-box />
            Catálogos
        </a>

        {{-- Con más espacio, más atajos, para que no queden cuatro botones sueltos. --}}
        @if (($w['nivel'] ?? 1) >= 2)
            <a href="{{ route('commercial.cotizaciones.index') }}" class="dw-acceso">
                <x-gravityui-file-text />
                Ver cotizaciones
            </a>
            <a href="{{ route('inventory.productos.index') }}" class="dw-acceso">
                <x-gravityui-box />
                Ver inventario
            </a>
        @endif

        @if (($w['nivel'] ?? 1) >= 3)
            <a href="{{ route('commercial.clientes.index') }}" class="dw-acceso">
                <x-gravityui-person />
                Ver clientes
            </a>
            <a href="{{ route('account') }}" class="dw-acceso">
                <x-gravityui-shield />
                Mi cuenta
            </a>
        @endif
    </div>
</div>
