<div class="dw">
    <div class="dw-head">
        <span class="dw-ico verde">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    <div class="dw-num">${{ number_format((float) ($w['monto'] ?? 0), 2) }}</div>
    <div class="dw-sub">{{ $w['cantidad'] ?? 0 }} {{ ($w['cantidad'] ?? 0) === 1 ? 'venta' : 'ventas' }} en el mes</div>

    @if (($w['nivel'] ?? 1) >= 2)
        @php $variacion = $w['variacion'] ?? null; @endphp
        <div class="dw-mini">
            <div>
                <b>${{ number_format((float) ($w['mes_anterior'] ?? 0), 0) }}</b>
                <span>Mes anterior</span>
            </div>
            <div>
                <b class="{{ $variacion === null ? '' : ($variacion >= 0 ? 'es-sube' : 'es-baja') }}">
                    {{ $variacion === null ? '—' : ($variacion >= 0 ? '+' : '') . number_format($variacion, 1) . '%' }}
                </b>
                <span>Variación</span>
            </div>
            <div>
                <b>${{ number_format((float) ($w['monto_total'] ?? 0), 0) }}</b>
                <span>Histórico</span>
            </div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">Histórico: ${{ number_format((float) ($w['monto_total'] ?? 0), 2) }}</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Mayores ventas del mes</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
