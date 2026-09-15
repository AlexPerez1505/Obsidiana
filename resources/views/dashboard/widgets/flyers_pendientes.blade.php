<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('marketing.aprobacion_flyers.index') }}" class="dw-link">Ver todas</a>
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Sin piezas en revisión por ahora.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $tarea)
                <a href="{{ route('marketing.aprobacion_flyers.index') }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $tarea->title }}</span>
                        <span class="dw-fila-s">{{ $tarea->user?->name ?? 'Sin responsable' }} · {{ $tarea->category ?: 'Sin categoría' }}</span>
                    </span>
                    <span class="badge">En revisión</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
