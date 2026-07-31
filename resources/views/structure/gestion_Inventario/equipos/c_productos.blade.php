@extends('layouts.dashboard')

@section('title', 'Nuevo Producto')

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="header-title">Nuevo Producto</h2>
                <p class="header-subtitle">Registra un nuevo equipo o producto</p>
            </div>
            <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Regresar
            </a>
        </div>
    </div>

    <div class="dashboard-card">
        <p style="color:rgba(255,255,255,0.55); margin:0;">Aquí irá el formulario para crear un nuevo producto.</p>
    </div>
@endsection
