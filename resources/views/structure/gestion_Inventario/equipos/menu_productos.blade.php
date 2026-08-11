@extends('layouts.dashboard')

@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Gestion de Inventario > Productos')

@php
    $products = $products ?? collect();
    $productTotal = is_countable($products) ? count($products) : 0;
@endphp

@push('head')
<style>
    .product-search {
        position: relative;
        margin-bottom: 18px;
    }
    .product-search svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        width: 18px;
        height: 18px;
    }
    .product-search input {
        width: 100%;
        padding: 11px 14px 11px 42px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 15px;
    }
    .product-search input:focus {
        outline: none;
        border-color: #7c3aed;
    }
    .product-state {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .product-action-menu { position: relative; display: inline-flex; }
    .product-action-list {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 210px;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        box-shadow: var(--shadow);
        display: none;
        z-index: 20;
    }
    .product-action-menu.is-open .product-action-list { display: grid; gap: 3px; }
    .product-action-list a, .product-action-list button {
        display: flex; align-items: center; gap: 8px;
        width: 100%; min-height: 34px; padding: 0 10px;
        border: 0; border-radius: 7px; background: transparent;
        color: var(--text); text-decoration: none; font-size: 13px; font-weight: 700;
        text-align: left; white-space: nowrap; cursor: pointer;
    }
    .product-action-list a:hover, .product-action-list button:hover { background: #eef4ff; color: #0879d0; }
    .product-action-list .danger { color: #ef4444; }
    .product-action-list .danger:hover { background: #fff1f2; color: #dc2626; }
    .product-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; color: var(--muted); font-size: 13px; font-weight: 600; }
    .product-thumb img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; display: block; }
</style>
@endpush

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('inventory.productos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo producto
        </a>
    </div>

    <x-ui.card>
        <div class="product-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="productSearch" type="search" placeholder="Buscar por nombre, codigo o categoria..." autocomplete="off">
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No. serie</th>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Subtipo</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    @forelse ($products as $product)
                        <tr class="{{ ! empty($product['selected']) ? 'is-selected' : '' }}" data-search="{{ strtolower($product['serial_number'].' '.$product['name'].' '.$product['category'].' '.$product['subtype'].' '.$product['status']) }}">
                            <td style="font-weight:700;">{{ $product['serial_number'] }}</td>
                            <td>
                                <span class="product-thumb" aria-label="Imagen de {{ $product['name'] }}">
                                    @if(! empty($product['image_path']))
                                        <img src="{{ asset($product['image_path']) }}" alt="Imagen de {{ $product['name'] }}">
                                    @else
                                        @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $product['thumb']])
                                    @endif
                                </span>
                            </td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['category'] }}</td>
                            <td>{{ $product['subtype'] }}</td>
                            <td>{{ $product['unit'] }}</td>
                            <td>{{ $product['stock'] }}</td>
                            <td>${{ number_format((float) $product['price'], 2) }} MXN</td>
                            <td><span class="product-state {{ $product['tone'] }}">{{ $product['status'] }}</span></td>
                            <td>
                                <div class="product-action-menu" data-product-action-menu>
                                    <button type="button" class="btn btn--ghost" style="padding:6px;" aria-haspopup="true" aria-expanded="false" data-product-action-toggle>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="1.8"></circle>
                                            <circle cx="12" cy="12" r="1.8"></circle>
                                            <circle cx="12" cy="19" r="1.8"></circle>
                                        </svg>
                                    </button>

                                    <div class="product-action-list" role="menu">
                                        <a href="{{ route('inventory.productos.show', ['producto' => $product['id']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver detalle
                                        </a>
                                        <a href="{{ route('inventory.productos.edit', ['producto' => $product['id']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            Editar
                                        </a>
                                        <a href="{{ route('inventory.stock.index', ['producto' => $product['id']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14"/><path d="M5 12h14"/><path d="M19 5v14"/></svg>
                                            Ajustar stock
                                        </a>
                                        <a href="{{ route('inventory.movimientos.index', ['producto' => $product['id']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
                                            Historial de movimientos
                                        </a>
                                        <form method="POST" action="{{ route('inventory.productos.destroy', ['producto' => $product['id']]) }}" onsubmit="return confirm('¿Eliminar este producto?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="danger" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay productos registrados. Agrega uno para gestionar el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="product-foot">
            <span id="productCount">{{ $productTotal === 0 ? 'Sin productos registrados' : 'Mostrando 1 a '.$productTotal.' de '.$productTotal.' resultados' }}</span>
            <button type="button" class="btn btn--ghost" style="font-size:13px;" @disabled($productTotal === 0)>Ver mas &gt;</button>
        </div>
    </x-ui.card>

    <script>
        const productSearch = document.getElementById('productSearch');
        const productRows = Array.from(document.querySelectorAll('#productsBody tr[data-search]'));
        const productCount = document.getElementById('productCount');
        const productTotal = productRows.length;

        if (productSearch) {
            productSearch.addEventListener('input', () => {
                const query = productSearch.value.trim().toLowerCase();
                let visible = 0;

                productRows.forEach((row) => {
                    const show = !query || row.dataset.search.includes(query);
                    row.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });

                productCount.textContent = visible === 0
                    ? 'Sin resultados'
                    : 'Mostrando 1 a ' + visible + ' de ' + productTotal + ' resultados';
            });
        }

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-product-action-toggle]');

            document.querySelectorAll('[data-product-action-menu]').forEach((menu) => {
                if (!toggle || menu !== toggle.closest('[data-product-action-menu]')) {
                    menu.classList.remove('is-open');
                    const button = menu.querySelector('[data-product-action-toggle]');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });

            if (!toggle) return;

            const menu = toggle.closest('[data-product-action-menu]');
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('[data-product-action-menu]').forEach((menu) => {
                menu.classList.remove('is-open');
                const button = menu.querySelector('[data-product-action-toggle]');
                if (button) button.setAttribute('aria-expanded', 'false');
            });
        });
    </script>
@endsection
