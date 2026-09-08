<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('commercial.cotizaciones.index') }}" class="dw-link">Ver todas</a>
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay cotizaciones.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $cot)
                @php
                    $cliente = trim(($cot->customer->nombre ?? '') . ' ' . ($cot->customer->apellido ?? ''));
                @endphp
                <a href="{{ route('commercial.cotizaciones.show', $cot) }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $cot->folio ?: 'Cotización #' . $cot->id }}</span>
                        <span class="dw-fila-s">{{ $cliente ?: 'Sin cliente' }}</span>
                    </span>
                    <span class="dw-fila-v">${{ number_format((float) $cot->total, 2) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
