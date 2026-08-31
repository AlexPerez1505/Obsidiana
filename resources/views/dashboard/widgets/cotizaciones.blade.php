<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('commercial.cotizaciones.index') }}" class="dw-link">Ver</a>
    </div>

    <div class="dw-num">{{ number_format($w['total'] ?? 0) }}</div>
    <div class="dw-sub">${{ number_format((float) ($w['monto'] ?? 0), 2) }} cotizado</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ $w['mes'] ?? 0 }}</b><span>Este mes</span></div>
            <div><b>${{ number_format((float) ($w['promedio'] ?? 0), 0) }}</b><span>Promedio</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['mes'] ?? 0 }} este mes</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Por estado</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
