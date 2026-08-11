@extends('layouts.dashboard')

@php
    $equipmentData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'brand' => '',
        'model' => '',
        'serial_number' => '',
        'warehouse' => '',
        'description' => '',
        'stock_current' => 0,
        'stock_min' => 0,
        'stock_max' => 0,
        'status' => 'Activo',
        'thumb' => 'tower',
    ], $equipment ?? []);
@endphp

@section('title', 'Detalle del equipo')
@section('page-title', 'Detalle del equipo')
@section('page-sub', 'Gestion de Inventario > Equipos > Detalle del equipo')

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <div style="display:flex; gap:10px;">
            <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Volver</a>
            <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipmentData['code']]) }}" class="btn" style="text-decoration:none;">Editar equipo</a>
        </div>
    </div>

    <div class="rgrid-2">
        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px;">Informacion general</x-ui.section-title>
            <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px; flex-wrap:wrap;">
                <div style="width:120px; height:120px; display:grid; place-items:center; border:1px dashed var(--border); border-radius:12px; background:var(--surface-2);">
                    @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipmentData['thumb']])
                </div>
                <div>
                    <h2 style="margin:0 0 8px; font-size:1.25rem; font-weight:800;">{{ $equipmentData['name'] }}</h2>
                    <p class="muted" style="margin:0;">Codigo: {{ $equipmentData['code'] }}</p>
                    <p class="muted" style="margin:4px 0 0;">Estado: {{ $equipmentData['status'] }}</p>
                </div>
            </div>

            <dl style="display:grid; gap:10px; margin:0;">
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Categoria:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['category'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Marca:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['brand'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Modelo:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['model'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">No. serie:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['serial_number'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Ubicacion:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['warehouse'] }}</dd>
                </div>
                <div style="display:grid; grid-template-columns:120px minmax(0,1fr); gap:12px;">
                    <dt class="muted">Descripcion:</dt>
                    <dd style="margin:0; font-weight:700;">{{ $equipmentData['description'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px;">Resumen de stock</x-ui.section-title>
            <div style="display:grid; gap:14px;">
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Stock actual</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">{{ $equipmentData['stock_current'] }} Pza</p>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Stock minimo</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">{{ $equipmentData['stock_min'] }} Pza</p>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 4px; font-size:0.9rem; font-weight:700;">Stock maximo</p>
                    <p style="margin:0; font-size:1.5rem; font-weight:800;">{{ $equipmentData['stock_max'] }} Pza</p>
                </div>
            </div>
        </x-ui.card>
    </div>
@endsection
