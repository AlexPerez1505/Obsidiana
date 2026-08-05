@extends('layouts.dashboard')

@php
    $productData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'unit' => 'Pza',
        'brand' => '',
        'model' => '',
        'description' => '',
        'stock_current' => 0,
        'stock_min' => 0,
        'stock_max' => 0,
        'warehouse' => '',
        'status' => 'Activo',
        'thumb' => 'scope',
    ], $product ?? []);
@endphp

@section('title', 'Detalle del producto')
@section('page-title', 'Detalle del producto')
@section('page-sub', 'Gestion de Inventario > Productos > Detalle del producto')

@push('head')
<style>
    .product-detail {
        display: grid;
        gap: 18px;
    }

    .product-detail__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .product-detail__intro {
        color: var(--muted);
        margin: 0;
        font-size: 0.94rem;
        font-weight: 600;
    }

    .product-detail__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .product-detail-card {
        display: grid;
        grid-template-columns: minmax(150px, 0.5fr) minmax(320px, 1.5fr) minmax(170px, 0.55fr);
        gap: 28px;
        align-items: center;
        min-height: 260px;
        padding: 30px 44px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: var(--shadow);
    }

    .product-detail-image {
        min-height: 170px;
        display: grid;
        place-items: center;
    }

    .product-detail-image svg {
        width: min(170px, 92%);
        height: auto;
    }

    .product-detail-main {
        min-width: 0;
    }

    .product-detail-main h3 {
        color: var(--text);
        font-size: 1.18rem;
        font-weight: 900;
        margin: 0 0 22px;
    }

    .product-detail-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }

    .product-detail-item {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 18px;
        align-items: baseline;
    }

    .product-detail-item dt {
        color: var(--muted);
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
    }

    .product-detail-item dd {
        color: var(--text);
        font-size: 0.9rem;
        font-weight: 900;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .product-detail-stock {
        display: grid;
        gap: 18px;
        align-content: center;
    }

    .product-detail-stock__label {
        color: var(--muted);
        display: block;
        font-size: 0.96rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .product-detail-stock__value {
        color: var(--text);
        display: block;
        font-size: 1.25rem;
        font-weight: 900;
        line-height: 1.1;
    }

    @media (max-width: 980px) {
        .product-detail-card {
            grid-template-columns: 1fr;
            padding: 24px;
            align-items: start;
        }

        .product-detail-image {
            min-height: 120px;
            justify-content: start;
        }

        .product-detail-stock {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 680px) {
        .product-detail__bar,
        .product-detail__actions {
            align-items: stretch;
            justify-content: stretch;
        }

        .product-detail__actions,
        .product-detail__actions .btn {
            width: 100%;
        }

        .product-detail-item {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .product-detail-stock {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="product-detail">
        <div class="product-detail__bar">
            <p class="product-detail__intro">Consulta el detalle del producto registrado en inventario.</p>

            <div class="product-detail__actions">
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Cancelar
                </a>
                <a href="{{ route('inventory.stock.index', ['producto' => $productData['code']]) }}" class="btn" style="text-decoration:none;">
                    Aplicar ajuste
                </a>
            </div>
        </div>

        <article class="product-detail-card">
            <div class="product-detail-image" aria-label="Imagen de {{ $productData['name'] }}">
                @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $productData['thumb']])
            </div>

            <div class="product-detail-main">
                <h3>{{ $productData['name'] }}</h3>

                <dl class="product-detail-list">
                    <div class="product-detail-item">
                        <dt>Codigo:</dt>
                        <dd>{{ $productData['code'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Categoria:</dt>
                        <dd>{{ $productData['category'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Marca:</dt>
                        <dd>{{ $productData['brand'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Modelo:</dt>
                        <dd>{{ $productData['model'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Unidad:</dt>
                        <dd>{{ $productData['unit'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Ubicacion:</dt>
                        <dd>{{ $productData['warehouse'] }}</dd>
                    </div>
                    <div class="product-detail-item">
                        <dt>Descripcion:</dt>
                        <dd>{{ $productData['description'] }}</dd>
                    </div>
                </dl>
            </div>

            <aside class="product-detail-stock" aria-label="Resumen de stock">
                <div>
                    <span class="product-detail-stock__label">Stock actual</span>
                    <strong class="product-detail-stock__value">{{ $productData['stock_current'] }} {{ $productData['unit'] }}</strong>
                </div>
                <div>
                    <span class="product-detail-stock__label">Stock minimo</span>
                    <strong class="product-detail-stock__value">{{ $productData['stock_min'] }} {{ $productData['unit'] }}</strong>
                </div>
                <div>
                    <span class="product-detail-stock__label">Stock maximo</span>
                    <strong class="product-detail-stock__value">{{ $productData['stock_max'] }} {{ $productData['unit'] }}</strong>
                </div>
                <div>
                    <span class="product-detail-stock__label">Estado</span>
                    <strong class="product-detail-stock__value">{{ $productData['status'] }}</strong>
                </div>
            </aside>
        </article>
    </section>
@endsection
