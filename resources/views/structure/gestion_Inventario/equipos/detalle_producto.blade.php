@extends('layouts.dashboard')

@php
    $productData = array_merge([
        'id' => '',
        'serial_number' => '',
        'name' => '',
        'category' => '',
        'subtype' => '',
        'unit' => 'Pza',
        'brand' => '',
        'model' => '',
        'description' => '',
        'price' => 0,
        'stock_current' => 0,
        'warehouse' => '',
        'status' => 'Activo',
        'thumb' => 'scope',
        'image_path' => '',
    ], $product ?? []);
@endphp

@section('title', 'Detalle del producto')
@section('page-title', 'Detalle del producto')
@section('page-sub', 'Gestion de Inventario > Productos > Detalle del producto')

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <div style="display:flex; gap:10px;">
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Volver</a>
            <a href="{{ route('inventory.productos.edit', ['producto' => $productData['id']]) }}" class="btn" style="text-decoration:none;">Editar producto</a>
        </div>
    </div>

    <div class="rgrid-2">
        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px;">Informacion general</x-ui.section-title>
            <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px; flex-wrap:wrap;">
                <div style="width:120px; height:120px; display:grid; place-items:center; border:1px dashed var(--border); border-radius:12px; background:var(--surface-2); overflow:hidden; padding:8px;">
                    @if(! empty($productData['image_path']))
                        <img src="{{ asset($productData['image_path']) }}" alt="Imagen de {{ $productData['name'] }}" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">
                    @else
                        @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $productData['thumb']])
                    @endif
                </div>
                <div>
                    <h2 style="margin:0 0 8px; font-size:1.25rem; font-weight:800;">{{ $productData['name'] }}</h2>
                    <p class="muted" style="margin:0;">No. serie: {{ $productData['serial_number'] }}</p>
                    <p class="muted" style="margin:4px 0 0;">Estado: {{ $productData['status'] }}</p>
                </div>
            </div>

            <dl style="display:grid; gap:10px; margin:0;">
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Tipo de equipo:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['category'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Subtipo:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['subtype'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Marca:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['brand'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Modelo:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['model'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Unidad:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['unit'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Almacen:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['warehouse'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Descripcion:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $productData['description'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px;">Resumen</x-ui.section-title>
            <div style="display:grid; gap:14px;">
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Stock</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">{{ $productData['stock_current'] }} {{ $productData['unit'] }}</p>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Precio</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">${{ number_format((float) $productData['price'], 2) }} MXN</p>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Estado</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">{{ $productData['status'] }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>
@endsection
