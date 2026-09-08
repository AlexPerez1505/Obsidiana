<div class="dw">
    <div class="dw-head">
        <span class="dw-ico rojo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 2v20l3-2 3 2 3-2 3 2 3-2V2l-3 2-3-2-3 2-3-2z"/><path d="M9 9h6M9 13h4"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    <div class="dw-num">{{ number_format($w['pendientes'] ?? 0) }}</div>
    <div class="dw-sub">de {{ $w['total'] ?? 0 }} facturas</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>${{ number_format((float) ($w['monto_pendiente'] ?? 0), 0) }}</b><span>Por cobrar</span></div>
            <div><b>{{ ($w['total'] ?? 0) - ($w['pendientes'] ?? 0) }}</b><span>Cobradas</span></div>
        </div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Pendientes recientes</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
