<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('commercial.clientes.index') }}" class="dw-link">Ver todos</a>
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay clientes.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $cliente)
                <a href="{{ route('commercial.clientes.show', $cliente) }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ trim($cliente->nombre . ' ' . $cliente->apellido) ?: 'Sin nombre' }}</span>
                        <span class="dw-fila-s">{{ $cliente->asesor?->name ?? 'Sin asesor' }}</span>
                    </span>
                    <span class="dw-fila-v">{{ $cliente->created_at?->format('d/m/Y') }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
