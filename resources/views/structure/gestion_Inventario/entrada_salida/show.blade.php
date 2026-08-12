@extends('layouts.dashboard')

@section('title', 'Detalle del Movimiento')
@section('page-title', 'Movimiento ' . $movement->folio)
@section('page-sub', 'Gestión de Inventario > Entrada / Salida > Detalle')

@section('content')
    <div style="display:flex; align-items:center; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">← Regresar</a>
    </div>

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 18px;">Detalle del movimiento</x-ui.section-title>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; font-size:14px;">
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Folio</strong> {{ $movement->folio }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Tipo</strong> {{ ucfirst($movement->movement_type) }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Fecha</strong> {{ $movement->movement_date->format('d/m/Y') }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Almacén</strong> {{ $movement->warehouse }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Producto</strong> {{ $movement->item_name }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Cantidad</strong> {{ $movement->quantity }} {{ $movement->unit }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Stock anterior</strong> {{ $movement->stock_before }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Stock actual</strong> {{ $movement->stock_after }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Referencia</strong> {{ $movement->reference ?: '-' }}</div>
            <div><strong style="display:block; color:var(--muted); font-size:12px;">Proveedor</strong> {{ $movement->supplier ?: '-' }}</div>
            <div style="grid-column:1 / -1;"><strong style="display:block; color:var(--muted); font-size:12px;">Notas</strong> {{ $movement->notes ?: '-' }}</div>
        </div>
    </x-ui.card>
@endsection
