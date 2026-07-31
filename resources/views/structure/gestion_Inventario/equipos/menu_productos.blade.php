@extends('layouts.dashboard')

@section('title', 'Equipos')

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="header-title">Equipos</h2>
                <p class="header-subtitle">Gestión de equipos y productos</p>
            </div>
            <a href="{{ route('inventory.equipos.create') }}" class="btn-gradient">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo producto
            </a>
        </div>
    </div>

    <div class="dashboard-card">
        <p style="color:rgba(255,255,255,0.55); margin:0;">Aquí se mostrará el listado de equipos y productos.</p>
    </div>
@endsection
