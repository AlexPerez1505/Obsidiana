<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <x-gravityui-persons />
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('commercial.clientes.index') }}" class="dw-link">Ver</a>
    </div>

    <div class="dw-num">{{ number_format($w['total'] ?? 0) }}</div>
    <div class="dw-sub">{{ $w['nuevos'] ?? 0 }} nuevos este mes</div>

    {{-- Al crecer, la tarjeta gana desglose en vez de quedarse vacía. --}}
    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ $w['activos'] ?? 0 }}</b><span>Activos</span></div>
            <div><b>{{ $w['inactivos'] ?? 0 }}</b><span>Inactivos</span></div>
            <div><b>{{ $w['con_promocion'] ?? 0 }}</b><span>Con promoción</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['inactivos'] ?? 0 }} inactivos</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Por categoría</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
