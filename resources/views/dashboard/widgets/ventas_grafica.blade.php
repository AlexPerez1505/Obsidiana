<div class="dw">
    <div class="dw-head">
        <span class="dw-ico verde">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="7"/><rect x="12" y="6" width="3" height="11"/><rect x="17" y="13" width="3" height="4"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    @php $maximo = (float) ($w['maximo'] ?? 0); @endphp

    @if ($maximo <= 0)
        <p class="dw-vacio">Todavía no hay ventas registradas.</p>
    @else
        <div class="dw-barras">
            @foreach ($w['meses'] as $mes)
                @php $alto = max(3, round(($mes['monto'] / $maximo) * 100)); @endphp
                <div class="dw-barra" title="{{ $mes['etiqueta'] }}: ${{ number_format($mes['monto'], 2) }}">
                    <span class="v">{{ $mes['monto'] > 0 ? '$' . number_format($mes['monto'] / 1000, 1) . 'k' : '' }}</span>
                    <span class="b" style="height:{{ $alto }}%"></span>
                    <span class="e">{{ $mes['etiqueta'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Detalle del periodo · ${{ number_format((float) ($w['total_periodo'] ?? 0), 2) }}</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
