@extends('layouts.dashboard')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Nuevo Movimiento')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Nuevo')

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <p class="header-subtitle" style="margin:0;">Registra una entrada, salida o transferencia de inventario</p>
            </div>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Regresar
            </a>
        </div>
    </div>

    <div class="dashboard-card">
        <p style="color:var(--muted); margin:0;">Aqui ira el formulario para crear un nuevo movimiento.</p>
    </div>
@endsection
