<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <x-gravityui-list-check />
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
