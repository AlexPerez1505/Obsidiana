{{--
    Paginador simple y reutilizable. Espera $paginator (cualquier instancia
    de LengthAwarePaginator) y respeta los filtros de la URL actual gracias
    a withQueryString() en el controlador.
--}}
@if ($paginator->hasPages())
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
        <span style="color:var(--muted); font-size:13px;">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </span>
        <div style="display:flex; gap:6px;">
            @if ($mostrarSelectorPagina ?? false)
                <label style="display:flex; align-items:center; gap:7px; color:var(--muted); font-size:13px; margin-right:4px;">
                    Página
                    <select aria-label="Seleccionar página" onchange="if (this.value) window.location.href=this.value" style="min-width:72px; height:36px; padding:0 9px; border:1px solid var(--border); border-radius:8px; background:var(--card); color:var(--text);">
                        @for ($pagina = 1; $pagina <= $paginator->lastPage(); $pagina++)
                            <option value="{{ $paginator->url($pagina) }}" @selected($pagina === $paginator->currentPage())>{{ $pagina }}</option>
                        @endfor
                    </select>
                </label>
            @endif

            @if ($paginator->onFirstPage())
                <span class="btn btn--ghost" style="opacity:.5; pointer-events:none;">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn--ghost" style="text-decoration:none;">Anterior</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn--ghost" style="text-decoration:none;">Siguiente</a>
            @else
                <span class="btn btn--ghost" style="opacity:.5; pointer-events:none;">Siguiente</span>
            @endif
        </div>
    </div>
@endif
