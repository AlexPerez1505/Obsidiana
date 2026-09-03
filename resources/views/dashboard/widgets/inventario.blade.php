<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
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
