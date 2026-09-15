<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('marketing.calendario.index') }}" class="dw-link">Ver calendario</a>
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Nada programado para esta semana.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $tarea)
                <a href="{{ route('marketing.calendario.index') }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $tarea->title }}</span>
                        <span class="dw-fila-s">
                            {{ $tarea->due_date?->translatedFormat('D d M') ?? 'Sin fecha' }}
                            @if ($tarea->category) · {{ $tarea->category }} @endif
                        </span>
                    </span>
                    <span class="badge">{{ ucfirst(str_replace('_', ' ', $tarea->status)) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
