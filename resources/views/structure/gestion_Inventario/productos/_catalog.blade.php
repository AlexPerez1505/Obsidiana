<div class="product-catalog" id="productCatalog">
    @forelse($productos as $producto)
        <article class="product-card">
            <div class="product-image">
                @if($producto->imagen_path)
                    <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->tipo_equipo }}">
                @else
                    <div class="no-img">—</div>
                @endif
            </div>

            <div class="product-info">
                <h3 class="product-name">{{ $producto->tipo_equipo }}</h3>
                <div class="product-meta">
                    <strong>Marca / Modelo:</strong> {{ $producto->marca ?: '—' }} {{ $producto->modelo }}
                </div>
                <div class="product-meta">
                    <strong>Serie:</strong> {{ $producto->no_serie ?: '—' }}
                </div>
                <div class="product-tags">
                    <span class="product-tag">{{ $producto->subtipo ?: 'Sin subtipo' }}</span>
                    @if($producto->stock > 0)
                        <span class="product-tag" style="background:rgba(34,197,94,.1); color:#22c55e;">Stock: {{ $producto->stock }}</span>
                    @endif
                </div>
            </div>

            <div class="product-price-row">
                <div class="product-price">
                    @if($producto->precio > 0)
                        ${{ number_format($producto->precio, 2) }}
                    @else
                        <span style="font-size:0.9rem; color:var(--muted);">Sin precio</span>
                    @endif
                </div>
                <div class="product-actions">
                    @if($producto->precio == 0)
                        <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-price" title="Agregar precio">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </a>
                    @else
                        <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-price" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar este producto?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="color:#ef4444; border-color:#ef4444;" title="Eliminar" aria-label="Eliminar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="products-empty">No hay productos registrados.</div>
    @endforelse
</div>
