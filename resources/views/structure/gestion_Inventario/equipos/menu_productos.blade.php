@extends('layouts.dashboard')

@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Gestion de Inventario > Productos')

@php
    $products = [
        ['code' => 'PRO-0001', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 3, 'status' => 'Activo', 'tone' => 'green', 'thumb' => 'scope'],
        ['code' => 'PRO-0002', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 2, 'status' => 'Mantenimiento', 'tone' => 'blue', 'thumb' => 'probe'],
        ['code' => 'PRO-0003', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 1, 'status' => 'Activo', 'tone' => 'green', 'thumb' => 'fiber'],
        ['code' => 'PRO-0004', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 3, 'status' => 'Inactivo', 'tone' => 'red', 'thumb' => 'tower'],
        ['code' => 'PRO-0005', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 1, 'status' => 'Activo', 'tone' => 'green', 'thumb' => 'control', 'selected' => true],
        ['code' => 'PRO-0006', 'name' => 'Endoscopio Flexible', 'category' => 'Endoscopia', 'unit' => 'Pza', 'stock' => 2, 'status' => 'Activo', 'tone' => 'green', 'thumb' => 'cable'],
    ];
@endphp

@push('head')
<style>
    .products-page {
        display: grid;
        gap: 18px;
    }

    .products-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .products-head p {
        margin: 0;
        color: #718096;
        font-size: 14px;
        font-weight: 600;
    }

    .product-create {
        min-height: 38px;
        margin-top: 22px;
        padding: 0 14px;
        border-radius: 4px;
        background: #158be8;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 7px 16px rgba(21, 139, 232, .22);
        white-space: nowrap;
    }

    .product-create:hover {
        background: #0879d0;
    }

    .product-create svg,
    .product-search svg,
    .product-action svg {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
    }

    .product-search {
        position: relative;
        padding: 13px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .62);
        border: 1px solid rgba(226, 232, 240, .78);
    }

    .product-search svg {
        position: absolute;
        left: 28px;
        top: 50%;
        transform: translateY(-50%);
        color: #718096;
        pointer-events: none;
    }

    .product-search input {
        width: 100%;
        height: 40px;
        padding: 0 14px 0 42px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .product-search input:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .14);
    }

    .products-table-panel {
        overflow: hidden;
        border: 1px solid #a8c5ff;
        border-radius: 5px;
        background: #fff;
    }

    .products-table-wrap {
        overflow-x: auto;
    }

    .products-table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        color: #202938;
        font-size: 13px;
    }

    .products-table th {
        padding: 17px 16px;
        background: #d8e2ff;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #a8c5ff;
    }

    .products-table td {
        height: 70px;
        padding: 11px 16px;
        border-bottom: 1px solid #a8c5ff;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }

    .products-table tr.is-selected td {
        border-top: 4px solid #0a8cff;
        border-bottom: 4px solid #0a8cff;
    }

    .products-table tr.is-selected td:first-child {
        border-left: 4px solid #0a8cff;
    }

    .products-table tr.is-selected td:last-child {
        border-right: 4px solid #0a8cff;
    }

    .product-thumb {
        width: 76px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-thumb svg {
        width: 64px;
        height: 42px;
        display: block;
    }

    .state-pill {
        min-width: 70px;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .state-pill.green {
        color: #16a329;
        border: 1px solid #22c943;
        background: #f7fff8;
    }

    .state-pill.blue {
        color: #1689ff;
        border: 1px solid #1689ff;
        background: #f5fbff;
    }

    .state-pill.red {
        color: #ff3131;
        border: 1px solid #ff4b4b;
        background: #fff8f8;
    }

    .product-action {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .product-action:hover {
        background: #eef4ff;
    }

    .product-action-menu {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .product-action-list {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 210px;
        padding: 6px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .14);
        display: none;
        z-index: 20;
    }

    .product-action-menu.is-open .product-action-list {
        display: grid;
        gap: 3px;
    }

    .product-action-list a,
    .product-action-list button {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-height: 34px;
        padding: 0 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #111827;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        font-family: inherit;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
    }

    .product-action-list a:hover,
    .product-action-list button:hover {
        background: #eef4ff;
        color: #0879d0;
    }

    .product-action-list a svg,
    .product-action-list button svg {
        width: 15px;
        height: 15px;
        flex: 0 0 auto;
    }

    .product-action-list .product-action-danger {
        color: #ef4444;
    }

    .product-action-list .product-action-danger:hover {
        background: #fff1f2;
        color: #dc2626;
    }

    .products-foot {
        min-height: 40px;
        padding: 0 16px;
        background: #d7e9ff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #1689ff;
        font-size: 12px;
        font-weight: 700;
    }

    .products-foot button {
        border: 0;
        background: transparent;
        color: #1689ff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    :root[data-theme="dark"] .product-search,
    :root[data-theme="dark"] .products-table-panel {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .product-search input,
    :root[data-theme="dark"] .products-table td {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .products-table th {
        background: rgba(10, 132, 255, .18);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .products-foot {
        background: rgba(10, 132, 255, .14);
    }

    :root[data-theme="dark"] .product-action {
        color: var(--text);
    }

    :root[data-theme="dark"] .product-action:hover,
    :root[data-theme="dark"] .product-action-list a:hover,
    :root[data-theme="dark"] .product-action-list button:hover {
        background: rgba(10, 132, 255, .16);
    }

    :root[data-theme="dark"] .product-action-list {
        background: var(--surface);
        border-color: var(--border);
        box-shadow: var(--shadow);
    }

    :root[data-theme="dark"] .product-action-list a,
    :root[data-theme="dark"] .product-action-list button {
        color: var(--text);
    }

    :root[data-theme="dark"] .product-action-list .product-action-danger {
        color: #f87171;
    }

    :root[data-theme="dark"] .product-action-list .product-action-danger:hover {
        background: rgba(248, 113, 113, .14);
        color: #fca5a5;
    }

    :root[data-theme="dark"] .products-head p,
    :root[data-theme="dark"] .product-search svg {
        color: var(--muted);
    }

    @media (max-width: 760px) {
        .products-head {
            align-items: stretch;
            flex-direction: column;
        }

        .product-create {
            width: 100%;
            margin-top: 0;
        }
    }
</style>
@endpush

@section('content')
    <section class="products-page">
        <div class="products-head">
            <div>
                <p>Administra todos los productos del inventario.</p>
            </div>

            <a href="{{ route('inventory.productos.create') }}" class="product-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Nuevo producto
            </a>
        </div>

        <div class="product-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="productSearch" type="search" placeholder="Buscar por nombre, codigo o categoria..." autocomplete="off">
        </div>

        <div class="products-table-panel">
            <div class="products-table-wrap">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Categoria</th>
                            <th>Unidad</th>
                            <th>Stock actual</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody">
                        @foreach ($products as $product)
                            <tr class="{{ ! empty($product['selected']) ? 'is-selected' : '' }}" data-search="{{ strtolower($product['code'].' '.$product['name'].' '.$product['category'].' '.$product['status']) }}">
                                <td>{{ $product['code'] }}</td>
                                <td>
                                    <span class="product-thumb" aria-label="Imagen de {{ $product['name'] }}">
                                        @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $product['thumb']])
                                    </span>
                                </td>
                                <td>{{ $product['name'] }}</td>
                                <td>{{ $product['category'] }}</td>
                                <td>{{ $product['unit'] }}</td>
                                <td>{{ $product['stock'] }}</td>
                                <td><span class="state-pill {{ $product['tone'] }}">{{ $product['status'] }}</span></td>
                                <td>
                                    <div class="product-action-menu" data-product-action-menu>
                                        <button class="product-action" type="button" aria-label="Acciones de {{ $product['code'] }}" aria-haspopup="true" aria-expanded="false" data-product-action-toggle>
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8"></circle>
                                                <circle cx="12" cy="12" r="1.8"></circle>
                                                <circle cx="12" cy="19" r="1.8"></circle>
                                            </svg>
                                        </button>

                                        <div class="product-action-list" role="menu">
                                            <a href="{{ route('inventory.productos.show', ['producto' => $product['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Ver detalle
                                            </a>
                                            <a href="{{ route('inventory.productos.edit', ['producto' => $product['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 20h9"></path>
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                                                </svg>
                                                Editar
                                            </a>
                                            <a href="{{ route('inventory.stock.index', ['producto' => $product['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 5v14"></path>
                                                    <path d="M5 12h14"></path>
                                                    <path d="M19 5v14"></path>
                                                </svg>
                                                Ajustar stock
                                            </a>
                                            <a href="{{ route('inventory.movimientos.index', ['producto' => $product['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 12a9 9 0 1 0 3-6.7"></path>
                                                    <path d="M3 4v5h5"></path>
                                                    <path d="M12 7v5l3 2"></path>
                                                </svg>
                                                Historial de movimientos
                                            </a>
                                            <button type="button" class="product-action-danger" role="menuitem" data-product-action-message="Eliminacion de producto pendiente de confirmar.">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M8 6V4h8v2"></path>
                                                    <path d="M19 6l-1 14H6L5 6"></path>
                                                    <path d="M10 11v5"></path>
                                                    <path d="M14 11v5"></path>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="products-foot">
                <span id="productCount">Mostrando 1 a {{ count($products) }} de 25 resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <script>
        const productSearch = document.getElementById('productSearch');
        const productRows = Array.from(document.querySelectorAll('#productsBody tr'));
        const productCount = document.getElementById('productCount');

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
                : 'Mostrando 1 a ' + visible + ' de 25 resultados';
        });

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-product-action-toggle]');
            const actionButton = event.target.closest('[data-product-action-message]');

            if (actionButton && window.showToast) {
                window.showToast(actionButton.dataset.productActionMessage);
            }

            document.querySelectorAll('[data-product-action-menu]').forEach((menu) => {
                if (!toggle || menu !== toggle.closest('[data-product-action-menu]')) {
                    menu.classList.remove('is-open');
                    const button = menu.querySelector('[data-product-action-toggle]');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (!toggle) {
                return;
            }

            const menu = toggle.closest('[data-product-action-menu]');
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-product-action-menu]').forEach((menu) => {
                menu.classList.remove('is-open');
                const button = menu.querySelector('[data-product-action-toggle]');
                if (button) {
                    button.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>
@endsection
