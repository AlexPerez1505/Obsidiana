<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <x-gravityui-box />
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('inventory.productos.index') }}" class="dw-link">Ver</a>
    </div>

    <div class="dw-num">{{ number_format($w['total'] ?? 0) }}</div>
    <div class="dw-sub">productos registrados</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ number_format($w['unidades'] ?? 0) }}</b><span>Unidades</span></div>
            <div><b class="{{ ($w['sin_stock'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['sin_stock'] ?? 0 }}</b><span>Sin existencia</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['sin_stock'] ?? 0 }} sin existencia</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Existencias más bajas</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
