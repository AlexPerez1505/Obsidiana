<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <x-gravityui-file-text />
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
