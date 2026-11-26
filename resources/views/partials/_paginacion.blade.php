{{--
    Paginador simple y reutilizable. Espera $paginator (cualquier instancia
    de LengthAwarePaginator) y respeta los filtros de la URL actual gracias
    a withQueryString() en el controlador.
--}}
@if ($paginator->hasPages())
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
        <span style="color:var(--muted); font-size:13px;">
            Mostrando {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </span>

        <nav aria-label="Paginación" style="display:flex; align-items:center; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
            @if ($paginator->onFirstPage())
                <span class="btn btn--ghost" style="opacity:.5; pointer-events:none;">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn--ghost" style="text-decoration:none;">Anterior</a>
            @endif

            @if ($mostrarSelectorPagina ?? false)
                @for ($pagina = 1; $pagina <= $paginator->lastPage(); $pagina++)
                    @if ($pagina === $paginator->currentPage())
                        <span aria-current="page" style="display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 10px; border:1px solid var(--accent); border-radius:8px; background:var(--accent); color:#fff; font-size:13px; font-weight:700;">{{ $pagina }}</span>
                    @else
                        <a href="{{ $paginator->url($pagina) }}" aria-label="Ir a la página {{ $pagina }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 10px; border:1px solid var(--border); border-radius:8px; background:var(--card); color:var(--text); font-size:13px; text-decoration:none;">{{ $pagina }}</a>
                    @endif
                @endfor
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn--ghost" style="text-decoration:none;">Siguiente</a>
            @else
                <span class="btn btn--ghost" style="opacity:.5; pointer-events:none;">Siguiente</span>
            @endif
        </nav>
    </div>
@endif
