<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">No tienes pendientes.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $tarea)
                <div class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $tarea->title }}</span>
                        <span class="dw-fila-s">{{ $tarea->category ?: 'Sin categoría' }}</span>
                    </span>
                    <span class="badge">{{ $tarea->status ?: '—' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
