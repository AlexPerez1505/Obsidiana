@extends('layouts.dashboard')
@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Gestion de Inventario > Productos')

@push('head')
<style>
    .products-page { display: grid; gap: 18px; }
    .products-head {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .products-head h2 { margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--text); }
    .products-head p { margin: 4px 0 0; color: var(--muted); font-size: 0.95rem; font-weight: 600; }
    .product-toolbar {
        display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
    }
    .product-toolbar input,
    .product-toolbar select {
        min-height: 40px; padding: 0 12px;
        border: 1px solid var(--border); border-radius: 10px;
        background: var(--surface); color: var(--text); font: inherit; font-size: 13px;
    }
    .product-catalog {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 18px;
    }
    .product-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        overflow: hidden;
        display: grid;
        grid-template-rows: 180px auto auto;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }
    .product-image {
        background: #f8fafc;
        display: grid; place-items: center;
        overflow: hidden; position: relative;
    }
    .product-image img { width: 100%; height: 100%; object-fit: contain; padding: 16px; }
    .product-image .no-img {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; font-size: 3.5rem; font-weight: 800;
    }
    .product-info { padding: 16px 18px; display: grid; gap: 8px; }
    .product-name { font-size: 1.05rem; font-weight: 900; color: var(--text); margin: 0; }
    .product-meta { font-size: 0.82rem; color: var(--muted); font-weight: 600; }
    .product-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .product-tag {
        font-size: 0.7rem; font-weight: 800; padding: 4px 10px;
        border-radius: 999px; background: rgba(21, 139, 232, .1); color: #158be8;
    }
    .product-price-row {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 12px 18px; border-top: 1px solid var(--border);
    }
    .product-price { font-size: 1.2rem; font-weight: 900; color: #158be8; }
    .product-actions { display: flex; gap: 8px; }
    .product-actions a,
    .product-actions button {
        padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border);
        background: var(--surface-2); color: var(--text); text-decoration: none;
        font-size: 12px; font-weight: 800; cursor: pointer;
    }
    .product-actions a:hover { background: #eef4ff; color: #0879d0; }
    .product-actions .btn-price { background: #158be8; color: #fff; border-color: #158be8; }
    .product-actions .btn-price:hover { background: #0879d0; }
    .products-empty {
        grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--muted); font-weight: 700;
    }
    :root[data-theme="dark"] .product-image { background: var(--surface-2); }
</style>
@endpush

@section('content')
    <div class="products-page">
        <div class="products-head">
            <div>
                <h2>Catálogo de productos</h2>
                <p>Consulta los equipos registrados y asigna sus precios.</p>
            </div>
        </div>

        <form class="product-toolbar" method="GET" action="{{ route('inventory.productos.index') }}">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar producto, serie, marca o modelo..." style="min-width:260px;">
            <select name="categoria">
                <option value="">Categoría</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria }}" @selected(($filters['categoria'] ?? '') === $categoria)>{{ $categoria }}</option>
                @endforeach
            </select>
            <select name="marca">
                <option value="">Marca</option>
                @foreach($marcas as $marca)
                    <option value="{{ $marca }}" @selected(($filters['marca'] ?? '') === $marca)>{{ $marca }}</option>
                @endforeach
            </select>
            <select name="modelo">
                <option value="">Modelo</option>
                @foreach($modelos as $modelo)
                    <option value="{{ $modelo }}" @selected(($filters['modelo'] ?? '') === $modelo)>{{ $modelo }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn" style="min-height:40px;">Filtrar</button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="min-height:40px; text-decoration:none;">Limpiar</a>
        </form>

        <div class="product-catalog">
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
                                <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-price">Agregar precio</a>
                            @else
                                <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-price">Editar</a>
                            @endif
                            <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar este producto?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color:#ef4444; border-color:#ef4444;">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="products-empty">No hay productos registrados.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toolbar = document.querySelector('.product-toolbar');
        if (!toolbar) return;

        // Evita que los select envien el formulario al cambiar de opcion
        toolbar.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function (e) {
                e.preventDefault();
            });
        });

        // Solo permite enviar el formulario desde el boton "Filtrar"
        toolbar.addEventListener('submit', function (e) {
            if (!e.submitter || e.submitter.textContent.trim() !== 'Filtrar') {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
